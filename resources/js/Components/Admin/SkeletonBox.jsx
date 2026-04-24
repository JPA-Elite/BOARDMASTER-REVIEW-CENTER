export default function SkeletonBox({
    width = 'w-full',
    height = 'h-4',
    rounded = 'rounded',
    className = '',
}) {
    return (
        <div
            className={`skeleton-shimmer ${width} ${height} ${rounded} ${className}`}
        />
    );
}