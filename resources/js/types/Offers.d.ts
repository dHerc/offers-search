export type Offer = {
    category: string,
    title: string,
    features: string,
    description: string,
    details: string,
}

export type PagedOffers = {
    data: Offer[];
    last_page: number;
    current_page: number;
    total: number;
}
