export default function SkeletonCard({ className = '' }) {
    return (
        <div
            className={`rounded-2xl border border-slate-200 bg-white px-4 py-4 sm:px-6 sm:py-5 ${className}`}
        >
            <div className="skeleton-shimmer h-4 w-24 rounded" />
            <div className="mt-4 skeleton-shimmer h-8 w-16 rounded" />
            <div className="mt-3 skeleton-shimmer h-3 w-20 rounded" />
        </div>
    );
}