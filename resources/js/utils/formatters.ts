// resources/js/utils/formatters.ts

/**
 * Formats a number as Indonesian Rupiah with thousand/million separators.
 * @param value The number to format.
 * @returns Formatted string, e.g., "Rp1.234.567"
 */
export const formatCurrency = (value: number): string => {
    const valueNumber = parseFloat(String(value));
    return `Rp${valueNumber.toLocaleString('id-ID')}`;
};

/**
 * Formats a number as a percentage.
 * @param value The number to format.
 * @param digits The number of decimal places to include.
 * @returns Formatted string, e.g., "12.34%"
 */
export function formatPercent(value: number, digits = 2) {
    return `${(value * 100).toFixed(digits)}%`;
}
