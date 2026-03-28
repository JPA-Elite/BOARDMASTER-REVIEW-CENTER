import http from 'k6/http';
import { check, group, sleep } from 'k6';
import exec from 'k6/execution';

const BASE_URL = (__ENV.BASE_URL || 'http://localhost:8000').replace(/\/$/, '');
const API_PREFIX = (__ENV.API_PREFIX || '/api').replace(/\/$/, '');
const MOBILE_BASE = `${BASE_URL}${API_PREFIX}/mobile`;

const USERS = parseUsers(__ENV.USERS || '');
const ENABLE_QUIZ = String(__ENV.ENABLE_QUIZ || 'true').toLowerCase() === 'true';
const QUIZ_QUESTION_COUNT = Math.max(1, parseInt(__ENV.QUIZ_QUESTION_COUNT || '10', 10));
const MIN_THINK = parseFloat(__ENV.MIN_THINK_TIME || '0.5');
const MAX_THINK = parseFloat(__ENV.MAX_THINK_TIME || '2.0');
const LOGIN_BACKOFF_BASE = Math.max(0.5, parseFloat(__ENV.LOGIN_BACKOFF_BASE_SECONDS || '1'));
const LOGIN_BACKOFF_MAX = Math.max(
  LOGIN_BACKOFF_BASE,
  parseFloat(__ENV.LOGIN_BACKOFF_MAX_SECONDS || '30')
);
const VALIDATE_USERS_IN_SETUP =
  String(__ENV.VALIDATE_USERS_IN_SETUP || 'true').toLowerCase() === 'true';

const TARGET_P95 = parseInt(__ENV.THRESHOLD_P95 || '1500', 10);
const FAIL_RATE = parseFloat(__ENV.THRESHOLD_FAIL_RATE || '0.02');

const SCENARIO = (String(__ENV.SCENARIO || 'smoke')).toLowerCase();

const scenarioConfig = buildScenarios(SCENARIO);

export const options = {
  scenarios: scenarioConfig,
  thresholds: {
    http_req_failed: [`rate<${FAIL_RATE}`],
    http_req_duration: [`p(95)<${TARGET_P95}`],
  },
  summaryTrendStats: ['avg', 'p(90)', 'p(95)', 'p(99)'],
};

const vuState = {
  token: null,
  subjectId: null,
  subjectLimit: null,
  nextLoginAttemptAt: 0,
  loginBackoffSeconds: LOGIN_BACKOFF_BASE,
};

function buildScenarios(name) {
  const scenarios = {
    smoke: {
      executor: 'constant-vus',
      vus: 1,
      duration: '1m',
      exec: 'authedFlow',
    },
    load: {
      executor: 'ramping-vus',
      stages: [
        { duration: '1m', target: 5 },
        { duration: '2m', target: 20 },
        { duration: '3m', target: 40 },
        { duration: '2m', target: 0 },
      ],
      exec: 'authedFlow',
    },
    stress: {
      executor: 'ramping-vus',
      stages: [
        { duration: '1m', target: 20 },
        { duration: '2m', target: 50 },
        { duration: '2m', target: 100 },
        { duration: '2m', target: 150 },
        { duration: '2m', target: 0 },
      ],
      exec: 'authedFlow',
    },
    spike: {
      executor: 'ramping-vus',
      stages: [
        { duration: '30s', target: 10 },
        { duration: '30s', target: 120 },
        { duration: '1m', target: 120 },
        { duration: '1m', target: 0 },
      ],
      exec: 'authedFlow',
    },
    soak: {
      executor: 'constant-vus',
      vus: 30,
      duration: '10m',
      exec: 'authedFlow',
    },
    public: {
      executor: 'constant-vus',
      vus: 5,
      duration: '3m',
      exec: 'publicFlow',
    },
  };

  if (name === 'all') {
    return {
      smoke: scenarios.smoke,
      load: scenarios.load,
      stress: scenarios.stress,
      spike: scenarios.spike,
      soak: scenarios.soak,
      public: scenarios.public,
    };
  }

  return scenarios[name] ? { [name]: scenarios[name] } : { smoke: scenarios.smoke };
}

export function setup() {
  if (!USERS.length) {
    console.error(
      'No USERS provided. Set USERS="email:password,email2:password2" to run authed scenarios.'
    );
    return { validUsers: [] };
  }

  if (!VALIDATE_USERS_IN_SETUP) {
    return { validUsers: USERS };
  }

  const validUsers = USERS.filter((user) => validateUser(user));
  const removed = USERS.length - validUsers.length;
  if (removed > 0) {
    console.warn(`Filtered out ${removed} invalid USERS during setup.`);
  }

  if (!validUsers.length) {
    console.error('No valid USERS could log in during setup.');
  }

  return { validUsers };
}

export function authedFlow(data) {
  const token = ensureToken(data);
  if (!token) {
    sleep(1);
    return;
  }

  group('me', () => {
    const res = apiGet('/me', token);
    check(res, { 'me 200': (r) => r.status === 200 });
  });

  group('dashboard metrics', () => {
    const res = apiGet('/dashboard/metrics', token);
    check(res, { 'metrics 200': (r) => r.status === 200 });
  });

  group('plans', () => {
    const res = apiGet('/plans', token, true);
    check(res, { 'plans 200': (r) => r.status === 200 });
  });

  if (Math.random() < 0.5) {
    group('referrals', () => {
      const res = apiGet('/referrals?page=1', token);
      check(res, { 'referrals 200': (r) => r.status === 200 });
    });
  }

  if (ENABLE_QUIZ) {
    const subjectInfo = ensureSubject(token);
    if (subjectInfo) {
      group('quiz generate + submit', () => {
        const questions = generateQuiz(token, subjectInfo);
        if (questions.length) {
          submitQuiz(token, subjectInfo.subjectId, questions);
        }
      });
    }
  }

  sleep(randomThink());
}

export default function (data) {
  authedFlow(data);
}

export function publicFlow() {
  group('public plans', () => {
    const res = http.get(`${MOBILE_BASE}/plans`, { tags: tagFor('GET /mobile/plans') });
    check(res, { 'public plans 200': (r) => r.status === 200 });
  });
  sleep(randomThink());
}

function ensureToken(data) {
  if (vuState.token) {
    return vuState.token;
  }

  if (nowSeconds() < vuState.nextLoginAttemptAt) {
    return null;
  }

  const user = pickUser(data);
  if (!user) {
    return null;
  }

  const res = http.post(
    `${MOBILE_BASE}/login`,
    JSON.stringify({ email: user.email, password: user.password }),
    {
      headers: jsonHeaders(),
      tags: tagFor('POST /mobile/login'),
    }
  );

  const ok = check(res, { 'login 200': (r) => r.status === 200 });
  if (!ok) {
    scheduleNextLoginAttempt();
    return null;
  }

  const payload = safeJson(res);
  vuState.token = payload?.token || null;
  if (!vuState.token) {
    scheduleNextLoginAttempt();
    return null;
  }

  vuState.loginBackoffSeconds = LOGIN_BACKOFF_BASE;
  vuState.nextLoginAttemptAt = 0;
  return vuState.token;
}

function ensureSubject(token) {
  if (vuState.subjectId) {
    return { subjectId: vuState.subjectId, limit: vuState.subjectLimit };
  }

  const res = apiGet('/subjects', token);
  if (res.status !== 200) {
    return null;
  }

  const payload = safeJson(res);
  const subjects = Array.isArray(payload?.data) ? payload.data : [];
  const accessible = subjects.find((item) => item.is_accessible && item.question_limit > 0);
  const fallback = accessible || subjects.find((item) => item.is_accessible) || subjects[0];
  if (!fallback) {
    return null;
  }

  vuState.subjectId = fallback.id;
  vuState.subjectLimit = fallback.question_limit || QUIZ_QUESTION_COUNT;

  return { subjectId: vuState.subjectId, limit: vuState.subjectLimit };
}

function generateQuiz(token, subjectInfo) {
  const total = Math.max(1, Math.min(QUIZ_QUESTION_COUNT, subjectInfo.limit || QUIZ_QUESTION_COUNT));
  const res = apiPost(
    '/quiz/generate',
    token,
    { subject_id: subjectInfo.subjectId, total_questions: total },
    'POST /mobile/quiz/generate'
  );

  check(res, { 'quiz generate 200': (r) => r.status === 200 });
  if (res.status !== 200) {
    return [];
  }

  const payload = safeJson(res);
  return Array.isArray(payload?.data) ? payload.data : [];
}

function submitQuiz(token, subjectId, questions) {
  const answers = questions.map((question) => {
    const labels = question?.choices ? Object.keys(question.choices) : [];
    const selected = labels.length
      ? labels[Math.floor(Math.random() * labels.length)]
      : null;
    return {
      question_id: question.id,
      selected_choice: selected,
    };
  });

  const res = apiPost(
    '/quiz/submit',
    token,
    { subject_id: subjectId, answers },
    'POST /mobile/quiz/submit'
  );

  check(res, { 'quiz submit 200': (r) => r.status === 200 });
}

function apiGet(path, token, allowPublic) {
  const headers = allowPublic ? jsonHeaders() : authHeaders(token);
  return http.get(`${MOBILE_BASE}${path}`, {
    headers,
    tags: tagFor(`GET /mobile${path.split('?')[0]}`),
  });
}

function apiPost(path, token, payload, tagName) {
  return http.post(`${MOBILE_BASE}${path}`, JSON.stringify(payload), {
    headers: authHeaders(token),
    tags: tagFor(tagName || `POST /mobile${path}`),
  });
}

function jsonHeaders() {
  return {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };
}

function authHeaders(token) {
  return {
    ...jsonHeaders(),
    Authorization: `Bearer ${token}`,
  };
}

function safeJson(res) {
  try {
    return res.json();
  } catch (_) {
    return null;
  }
}

function tagFor(name) {
  return { name };
}

function validateUser(user) {
  const res = http.post(
    `${MOBILE_BASE}/login`,
    JSON.stringify({ email: user.email, password: user.password }),
    {
      headers: jsonHeaders(),
      tags: tagFor('SETUP POST /mobile/login'),
    }
  );

  return res.status === 200;
}

function parseUsers(raw) {
  return raw
    .split(/[,\n;]/)
    .map((value) => value.trim())
    .filter(Boolean)
    .map((entry) => {
      const parts = entry.split(':');
      if (parts.length < 2) {
        return null;
      }
      const email = parts.shift().trim();
      const password = parts.join(':').trim();
      if (!email || !password) {
        return null;
      }
      return { email, password };
    })
    .filter(Boolean);
}

function pickUser(data) {
  const runUsers = getRunUsers(data);
  if (!runUsers.length) {
    return null;
  }
  const index = (exec.vu.idInTest - 1) % runUsers.length;
  return runUsers[index];
}

function getRunUsers(data) {
  if (data && Array.isArray(data.validUsers) && data.validUsers.length) {
    return data.validUsers;
  }
  return USERS;
}

function scheduleNextLoginAttempt() {
  const jitter = Math.random() * LOGIN_BACKOFF_BASE;
  vuState.nextLoginAttemptAt = nowSeconds() + vuState.loginBackoffSeconds + jitter;
  vuState.loginBackoffSeconds = Math.min(LOGIN_BACKOFF_MAX, vuState.loginBackoffSeconds * 2);
}

function nowSeconds() {
  return Date.now() / 1000;
}

function randomThink() {
  if (MAX_THINK <= MIN_THINK) {
    return MIN_THINK;
  }
  return MIN_THINK + Math.random() * (MAX_THINK - MIN_THINK);
}
