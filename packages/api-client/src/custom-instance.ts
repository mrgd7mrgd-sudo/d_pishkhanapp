import Axios, { type AxiosRequestConfig, type AxiosResponse } from 'axios';

export const AXIOS_INSTANCE = Axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

export const customInstance = <T>(
  config: AxiosRequestConfig,
  options?: AxiosRequestConfig,
): Promise<T> => {
  const source = Axios.CancelToken.source();
  const promise = AXIOS_INSTANCE({
    ...config,
    ...options,
    cancelToken: source.token,
  }).then(({ data }: AxiosResponse<T>) => data);

  // @ts-expect-error cancel token attached to promise
  promise.cancel = () => {
    source.cancel('Query was cancelled');
  };

  return promise;
};

export default customInstance;
