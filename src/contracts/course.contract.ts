export interface CourseCardContract {
  title: string;
  slug: string;
  thumbnail: string;
  short_description: string;
  duration: string;
  rating: string;
  original_price: string;
  discount_price: string;
  category: string;
}

export interface CourseDetailContract {
  title: string;
  slug: string;
  thumbnail: string;
  overview: string;
  syllabus: string;
  instructor: string;
  duration: string;
  student_limit: string;
  pdf_url: string;
}
