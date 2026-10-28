import { EventCardContract, EventDetailContract } from '../contracts/event.contract';

export const fallbackEvents: (EventCardContract & Omit<EventDetailContract, "title">)[] = [
  {
    "title": "Global Web Development Summit 2026",
    "slug": "global-web-dev-summit-2026",
    "event_date": "2026-10-15",
    "location": "Online / San Francisco",
    "short_description": "The biggest summit of the year for modern frontend developers.",
    "description": "Join thousands of developers worldwide to explore React, Angular, Vue, and high-performance server contracts.",
    "speaker_name": "Sarah Jenkins",
    "capacity": "500",
    "ticket_price": "49"
  },
  {
    "title": "WordPress Decoupled & Headless Workshop",
    "slug": "wp-decoupled-headless-workshop",
    "event_date": "2026-11-20",
    "location": "Austin, Texas",
    "short_description": "Learn how to build headless WordPress setups using React.",
    "description": "A deep dive workshop focusing on optimizing headless API endpoints, WP Transients, and React static fallback strategies.",
    "speaker_name": "Robert Miller",
    "capacity": "80",
    "ticket_price": "99"
  }
];
