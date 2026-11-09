/** Laravel paginated resource collection envelope. */
export interface PaginatedResponse<T> {
  data: T[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
  };
}

export interface ResourceResponse<T> {
  data: T;
}

export interface ListParams {
  search?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  [key: string]: string | number | boolean | undefined;
}
