import { EventCardContract, EventDetailContract } from '../contracts/event.contract';
import { fallbackEvents } from '../data/fallback-events';

export async function getEvents(): Promise<EventCardContract[]> {
  try {
    const res = await fetch('/wp-json/wp-sync/v1/events');
    if (!res.ok) throw new Error('API Response Error');
    const list: any[] = await res.json();
    return list.map((item) => ({
      title: item.title,
      slug: item.slug,
      event_date: item.event_date,
      location: item.location,
      short_description: item.short_description,
      ticket_price: item.ticket_price,
    }));
  } catch (err) {
    console.warn("Events API fetch failed, loading fallback.", err);
    return fallbackEvents;
  }
}

export async function getUpcomingEvents(): Promise<EventCardContract[]> {
  try {
    const res = await fetch('/wp-json/wp-sync/v1/events?upcoming=1');
    if (!res.ok) throw new Error('API Response Error');
    const list: any[] = await res.json();
    return list.map((item) => ({
      title: item.title,
      slug: item.slug,
      event_date: item.event_date,
      location: item.location,
      short_description: item.short_description,
      ticket_price: item.ticket_price,
    }));
  } catch (err) {
    console.warn("Upcoming Events API fetch failed, loading fallback.", err);
    return fallbackEvents.slice(0, 1);
  }
}

export async function getEventBySlug(slug: string): Promise<EventDetailContract | null> {
  try {
    const res = await fetch(`/wp-json/wp-sync/v1/events/${slug}`);
    if (!res.ok) throw new Error('API Response Error');
    const item = await res.json();
    return {
      title: item.title,
      slug: item.slug,
      event_date: item.event_date,
      location: item.location,
      description: item.description,
      speaker_name: item.speaker_name,
      capacity: item.capacity,
      ticket_price: item.ticket_price,
    };
  } catch (err) {
    console.warn(`Event detail API fetch for ${slug} failed, loading fallback.`, err);
    const fallback = fallbackEvents.find((e) => e.slug === slug);
    return fallback || null;
  }
}
