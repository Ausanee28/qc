const twoDigits = (value) => String(value).padStart(2, '0');

export const isoToDisplayDate = (value) => {
    if (!value) return '';
    const text = String(value).trim();
    if (/^\d{2}-\d{2}-\d{4}$/.test(text)) return text;

    const dateMatch = /^(\d{4})-(\d{2})-(\d{2})/.exec(text);
    return dateMatch ? `${dateMatch[3]}-${dateMatch[2]}-${dateMatch[1]}` : text;
};

export const displayToIsoDate = (value) => {
    if (!value) return '';
    const text = String(value).trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(text)) return text;

    const displayMatch = /^(\d{2})-(\d{2})-(\d{4})$/.exec(text);
    return displayMatch ? `${displayMatch[3]}-${displayMatch[2]}-${displayMatch[1]}` : '';
};

export const formatDisplayDate = (value) => isoToDisplayDate(value) || '-';

export const formatDisplayDateTime = (value) => {
    if (!value) return '-';

    const text = String(value).trim();
    const timeMatch = /(?:T|\s)(\d{2}):(\d{2})/.exec(text);
    const date = isoToDisplayDate(text);

    if (!date || date === text) {
        return text;
    }

    return timeMatch ? `${date} ${timeMatch[1]}:${timeMatch[2]}` : date;
};

export const todayIsoDate = () => {
    const now = new Date();
    return `${now.getFullYear()}-${twoDigits(now.getMonth() + 1)}-${twoDigits(now.getDate())}`;
};

export const monthStartIsoDate = () => {
    const now = new Date();
    return `${now.getFullYear()}-${twoDigits(now.getMonth() + 1)}-01`;
};
