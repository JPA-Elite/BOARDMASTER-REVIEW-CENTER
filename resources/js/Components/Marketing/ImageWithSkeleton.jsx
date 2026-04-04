import { useState } from 'react';

export default function ImageWithSkeleton({
    src,
    alt = '',
    className = '',
    wrapperClassName = '',
    skeletonClassName = '',
    skeletonHeightClassName = '',
    loading = 'lazy',
    fetchPriority,
}) {
    const [loaded, setLoaded] = useState(false);

    return (
        <div
            className={`relative ${wrapperClassName} ${
                !loaded ? skeletonHeightClassName : ''
            }`.trim()}
        >
            {!loaded && (
                <div
                    className={`skeleton-shimmer absolute inset-0 ${skeletonClassName}`.trim()}
                />
            )}
            <img
                src={src}
                alt={alt}
                loading={loading}
                fetchPriority={fetchPriority}
                className={`${className} ${loaded ? 'opacity-100' : 'opacity-0'} transition-opacity duration-300`.trim()}
                onLoad={() => setLoaded(true)}
                onError={() => setLoaded(true)}
            />
        </div>
    );
}
