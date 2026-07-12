export type GreetingKey = 'morning' | 'afternoon' | 'evening';

/** Pick a time-of-day greeting key. Split at noon and 6pm local time. */
export function getGreetingKey(date: Date = new Date()): GreetingKey {
  const hour = date.getHours();
  if (hour < 12) return 'morning';
  if (hour < 18) return 'afternoon';
  return 'evening';
}
