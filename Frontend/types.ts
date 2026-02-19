export interface User {
  id: number;
  email: string;
  name?: string;
}

export interface Link {
  id: number;
  short_code: string;
  original_url: string;
  user_id?: number;
  clicks: number;
  created_at: string;
}

export interface LinkStats {
  id: number;
  short_code: string;
  original_url: string;
  clicks: number;
  clicks_by_date: { date: string; count: number }[];
  top_countries: { country: string; count: number }[];
  top_browsers: { browser: string; count: number }[];
}

export interface AuthResponse {
  user: User;
}

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  limit: number;
  total_pages: number;
}
