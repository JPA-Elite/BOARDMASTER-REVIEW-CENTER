import { useState, useEffect } from 'react';

export function useInertiaLoading() {
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (typeof window === 'undefined' || !window.Inertia) {
            return;
        }

        const handleStart = () => setLoading(true);
        const handleFinish = () => setLoading(false);

        window.Inertia.on('start', handleStart);
        window.Inertia.on('finish', handleFinish);

        return () => {
            window.Inertia.off('start', handleStart);
            window.Inertia.off('finish', handleFinish);
        };
    }, []);

    return loading;
}