import { CourseCardContract, CourseDetailContract } from '../contracts/course.contract';
import { fallbackCourses } from '../data/fallback-courses';

export async function getCourses(): Promise<CourseCardContract[]> {
  try {
    const res = await fetch('/wp-json/wp-sync/v1/courses');
    if (!res.ok) throw new Error('API Response Error');
    const list: any[] = await res.json();
    return list.map((item) => ({
      title: item.title,
      slug: item.slug,
      thumbnail: item.thumbnail,
      short_description: item.short_description,
      duration: item.duration,
      rating: item.rating,
      original_price: item.original_price,
      discount_price: item.discount_price,
      category: item.category,
    }));
  } catch (err) {
    console.warn("Courses API fetch failed, loading static/cache fallback.", err);
    return fallbackCourses;
  }
}

export async function getFeaturedCourses(): Promise<CourseCardContract[]> {
  try {
    const res = await fetch('/wp-json/wp-sync/v1/courses?featured=1');
    if (!res.ok) throw new Error('API Response Error');
    const list: any[] = await res.json();
    return list.map((item) => ({
      title: item.title,
      slug: item.slug,
      thumbnail: item.thumbnail,
      short_description: item.short_description,
      duration: item.duration,
      rating: item.rating,
      original_price: item.original_price,
      discount_price: item.discount_price,
      category: item.category,
    }));
  } catch (err) {
    console.warn("Featured Courses API fetch failed, loading fallback.", err);
    return fallbackCourses.slice(0, 1);
  }
}

export async function getCourseBySlug(slug: string): Promise<CourseDetailContract | null> {
  try {
    const res = await fetch(`/wp-json/wp-sync/v1/courses/${slug}`);
    if (!res.ok) throw new Error('API Response Error');
    const item = await res.json();
    return {
      title: item.title,
      slug: item.slug,
      thumbnail: item.thumbnail,
      overview: item.overview,
      syllabus: item.syllabus,
      instructor: item.instructor,
      duration: item.duration,
      student_limit: item.student_limit,
      pdf_url: item.pdf_url,
    };
  } catch (err) {
    console.warn(`Course detail API fetch for ${slug} failed, loading fallback.`, err);
    const fallback = fallbackCourses.find((c) => c.slug === slug);
    return fallback || null;
  }
}
