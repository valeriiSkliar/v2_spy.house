import { initSingleServiceRating } from "./single-service-rating";
import { initServicesPagination } from "./pagination-handler";

const initializeServiceComponents = () => {
    initSingleServiceRating();
    initServicesPagination();
};

export { initializeServiceComponents };
