/**
 * A file size for people: "1.2 MB", "340 KB", "12 bytes".
 */
export function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} ${bytes === 1 ? 'byte' : 'bytes'}`;
    }

    const units = ['KB', 'MB', 'GB'];
    let value = bytes / 1024;
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit++;
    }

    return `${value >= 10 || Number.isInteger(value) ? Math.round(value) : value.toFixed(1)} ${units[unit]}`;
}
