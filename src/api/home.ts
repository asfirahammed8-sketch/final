import { HomeDataContract } from '../contracts/home.contract';
import { fallbackHomeData } from '../data/fallback-home';

export async function getHomeData(): Promise<HomeDataContract> {
  try {
    const res = await fetch('/wp-json/wp-sync/v1/home');
    if (!res.ok) throw new Error('API Response Error');
    const data = await res.json();
    return {
      hero_title: data.hero_title || fallbackHomeData.hero_title,
      hero_subtitle: data.hero_subtitle || fallbackHomeData.hero_subtitle,
      hero_cta_text: data.hero_cta_text || fallbackHomeData.hero_cta_text,
      stats_total_students: data.stats_total_students || fallbackHomeData.stats_total_students,
      stats_active_courses: data.stats_active_courses || fallbackHomeData.stats_active_courses,
    };
  } catch (err) {
    console.warn("Home API fetch failed, loading static/cache fallback.", err);
    return fallbackHomeData;
  }
}
