export default function SkeletonChart({ className = '' }) {
    return (
        <div
            className={`rounded-xl border border-slate-100 bg-slate-50/40 p-3 ${className}`}
        >
            <div className="flex justify-between mb-4">
                <div className="skeleton-shimmer h-5 w-32 rounded" />
                <div className="flex gap-2">
                    <div className="skeleton-shimmer h-6 w-16 rounded" />
                    <div className="skeleton-shimmer h-6 w-16 rounded" />
                </div>
            </div>
            <div className="h-[250px] relative">
                {/* Y-axis labels */}
                <div className="absolute left-0 top-0 bottom-0 w-8 flex flex-col justify-between">
                    {Array.from({ length: 5 }).map((_, i) => (
                        <div key={i} className="skeleton-shimmer h-3 w-6 rounded" />
                    ))}
                </div>
                {/* Chart area */}
                <div className="ml-10 h-full flex items-end justify-around gap-2">
                    {Array.from({ length: 12 }).map((_, i) => (
                        <div
                            key={i}
                            className="skeleton-shimmer w-6 rounded-t"
                            style={{ height: `${20 + Math.random() * 60}%` }}
                        />
                    ))}
                </div>
            </div>
        </div>
    );
}