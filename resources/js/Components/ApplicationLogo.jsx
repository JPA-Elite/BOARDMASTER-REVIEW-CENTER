export default function ApplicationLogo({
    className = '',
    alt = 'Board Master Review Center',
    ...props
}) {
    return (
        <img
            src="/images/boardmaster.png"
            alt={alt}
            className={className}
            {...props}
        />
    );
}
