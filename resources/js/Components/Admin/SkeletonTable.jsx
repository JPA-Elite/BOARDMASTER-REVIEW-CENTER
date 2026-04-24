export default function SkeletonTable({
    rows = 5,
    columns = 5,
    className = '',
}) {
    return (
        <div className={`overflow-x-auto ${className}`}>
            <table className="min-w-full text-left text-sm">
                <thead className="text-slate-500">
                    <tr>
                        {Array.from({ length: columns }).map((_, index) => (
                            <th key={index} className="px-3 py-2">
                                <div className="skeleton-shimmer h-4 w-20 rounded" />
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {Array.from({ length: rows }).map((_, rowIndex) => (
                        <tr key={rowIndex} className="border-t border-slate-200">
                            {Array.from({ length: columns }).map((_, colIndex) => (
                                <td
                                    key={colIndex}
                                    className="px-3 py-2"
                                >
                                    <div className="skeleton-shimmer h-4 w-full rounded" />
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}