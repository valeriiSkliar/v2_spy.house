class ServiceAPIManager {
  constructor(baseUrl = '/api/services') {
    this.baseUrl = baseUrl;
  }

  async fetchServices(filters = {}, page = 1) {
    const queryParams = new URLSearchParams(filters);
    queryParams.set('page', page);
    try {
      const response = await fetch(`${this.baseUrl}?${queryParams.toString()}`);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return await response.json();
    } catch (error) {
      console.error('Error fetching services:', error);
      throw error;
    }
  }

  async fetchServiceById(id) {
    try {
      const response = await fetch(`${this.baseUrl}/${id}`);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return await response.json();
    } catch (error) {
      console.error(`Error fetching service with ID ${id}:`, error);
      throw error;
    }
  }
}

export default ServiceAPIManager;
