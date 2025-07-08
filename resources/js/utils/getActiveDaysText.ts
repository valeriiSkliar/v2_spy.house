/**
 * Возвращает строку вида "Active X days" с учётом ошибок и fallback.
 * @param activityDate - строка даты (ISO или Date-совместимая)
 * @param fallback - строка по умолчанию, если дата некорректна
 */
export function getActiveDaysText(activityDate?: string | null, fallback: string = 'Active N/A') {
  if (!activityDate) return fallback;
  const date = new Date(activityDate);
  const now = new Date();
  if (isNaN(date.getTime())) return fallback;

  // Если дата в будущем — показываем 0 дней
  let diff = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24));
  if (diff < 0) diff = 0;

  if (diff === 1) return 'Active 1 day';
  if (diff >= 0) return `Active ${diff} days`;
  return fallback;
}
