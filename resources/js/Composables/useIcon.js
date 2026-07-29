
export const detectIconType = (icon) => {
    if (!icon) {
        return 'none';
    }
    if (icon.startsWith('http')) {
        return 'url';
    }
    if (icon.includes('.') || icon.includes('/')) {
        return 'file';
    }
    if (/^[a-z0-9-]+$/.test(icon) && icon.length > 1) {
        return 'lucide';
    }
    return 'emoji';
};

export const isImageIcon = (icon) => {
    const type = detectIconType(icon);
    return type === 'url' || type === 'file';
};

export const resolveImageUrl = (icon, type = null) => {
    type = type || detectIconType(icon);
    if (type === 'url') {
        return icon;
    }
    if (type === 'file') {
        // Assuming file icons are stored in '/storage/'
        return `/storage/${icon}`;
    }
    return null;
};

export const kebabToPascal = (str) => {
    return str.replace(/(^\w|-\w)/g, (g) => g.replace('-', '').toUpperCase());
};

export const CATEGORY_ICON_COLORS = {
    Income: 'text-emerald-400',
    Expense: 'text-red-400',
    Transfer: 'text-blue-400',
    Debt: 'text-amber-400',
    Receivable: 'text-fuchsia-400',
};

export const getCategoryIconColor = (typeName) => {
    return CATEGORY_ICON_COLORS[typeName] || 'text-gray-500';
};
