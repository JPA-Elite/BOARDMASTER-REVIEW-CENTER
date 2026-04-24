import { User } from 'lucide-react';
import { useState } from 'react';

function stringToHashIndex(value, mod) {
    let h = 0;
    for (let i = 0; i < value.length; i++) {
        h = (h << 5) - h + value.charCodeAt(i);
        h |= 0;
    }
    return Math.abs(h) % mod;
}

function getInitials(text) {
    if (!text) return '';
    const parts = String(text).trim().split(/\s+/);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
}

export default function NotificationAvatar({
    label = '',
    size = 48,
    imageUrl = null,
}) {
    const [imageFailed, setImageFailed] = useState(false);
    const palette = [
        ['#06B6D4', '#3B82F6'], // teal -> blue
        ['#F97316', '#FB7185'], // orange -> pink
        ['#A78BFA', '#7C3AED'], // light purple -> indigo
        ['#34D399', '#10B981'], // green duo
        ['#F59E0B', '#F97316'], // amber -> orange
        ['#60A5FA', '#38BDF8'], // sky blues
        ['#F472B6', '#FB7185'], // pinks
        ['#FCA5A5', '#F87171'], // soft red
    ];

    const key = String(label || '').toLowerCase();
    const idx = stringToHashIndex(key, palette.length);
    const [c1, c2] = palette[idx];
    const initials = getInitials(label || 'N');

    const style = {
        width: size,
        height: size,
        backgroundImage: `linear-gradient(135deg, ${c1}, ${c2})`,
    };

    return (
        <div className="flex-shrink-0">
            <div
                className="rounded-full shadow-md ring-2 ring-white overflow-hidden inline-flex items-center justify-center"
                style={style}
                title={label}
            >
                {typeof imageUrl === 'string' && imageUrl.trim() !== '' && !imageFailed ? (
                    <img
                        src={imageUrl}
                        alt={label}
                        className="block h-full w-full object-cover"
                        onError={() => setImageFailed(true)}
                    />
                ) : (
                    <div
                        className="inline-flex h-full w-full items-center justify-center text-white font-semibold"
                        aria-hidden
                    >
                        {initials ? (
                            <span className="select-none text-sm leading-none">
                                {initials}
                            </span>
                        ) : (
                            <User className="size-4 text-white" />
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
