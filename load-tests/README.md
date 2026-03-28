# Load / Stress Testing

This folder contains a k6 load test that targets the mobile API. It exercises
the same flows the app uses: login, plans, dashboard metrics, referrals, quiz
generate + submit.

## Prerequisites
- k6 installed locally (https://k6.io/docs/get-started/installation/)
- At least one valid user account for the test environment

## Quick start

```bash
k6 run load-tests/k6/mobile.js \
  -e BASE_URL=http://127.0.0.1:8000 \
  -e USERS="email1@test.com:Password1!,email2@test.com:Password1!" \
  -e SCENARIO=smoke
```

## Scenarios

Set `SCENARIO` to run one of the built-in load profiles:

- `smoke` (default): quick sanity check
- `load`: gradual ramp to moderate traffic
- `stress`: ramp above expected traffic
- `spike`: sudden traffic burst
- `soak`: longer steady run
- `public`: unauthenticated plans only
- `all`: runs all scenarios together

## Optional tuning

Environment variables:

- `BASE_URL` (default `http://127.0.0.1:8000`)
- `API_PREFIX` (default `/api`)
- `USERS` (required for authed flows) format: `email:password,email2:password2`
- `ENABLE_QUIZ` (default `true`)
- `QUIZ_QUESTION_COUNT` (default `10`)
- `MIN_THINK_TIME` (default `0.5`)
- `MAX_THINK_TIME` (default `2.0`)
- `THRESHOLD_P95` (default `1500`) in ms
- `THRESHOLD_FAIL_RATE` (default `0.02`)

Example stress run:

```bash
k6 run load-tests/k6/mobile.js \
  -e BASE_URL=http://127.0.0.1:8000 \
  -e USERS="email1@test.com:Password1!,email2@test.com:Password1!" \
  -e SCENARIO=stress \
  -e THRESHOLD_P95=2000 \
  -e QUIZ_QUESTION_COUNT=15
```

## Notes
- Use dedicated test users; the test will create quiz attempts.
- For large runs, consider disabling `ENABLE_QUIZ` to focus on read-heavy API performance.
- Watch DB, queue, and email services during stress tests.
