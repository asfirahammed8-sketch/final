export interface EventCardContract {
  title: string;
  slug: string;
  event_date: string;
  location: string;
  short_description: string;
  ticket_price: string;
}

export interface EventDetailContract {
  title: string;
  slug: string;
  event_date: string;
  location: string;
  description: string;
  speaker_name: string;
  capacity: string;
  ticket_price: string;
}
