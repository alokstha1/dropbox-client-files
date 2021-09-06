import axios from 'axios';

export const HTTP = axios.create({
  baseURL: `https://api.dropboxapi.com/2`,
  headers: {'X-WP-Nonce':variables.nonce}
});
