import { useState, useEffect, useCallback } from 'react';

export const useApi = (apiFunction, params = null, immediate = true) => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(immediate);
  const [error, setError] = useState(null);

  const execute = useCallback(async (executeParams = params) => {
    setLoading(true);
    setError(null);
    try {
      const response = executeParams
        ? await apiFunction(executeParams)
        : await apiFunction();
      setData(response.data);
      return response.data;
    } catch (err) {
      setError(err);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [apiFunction, params]);

  useEffect(() => {
    if (immediate) {
      execute();
    }
  }, []);

  return { data, loading, error, execute, setData };
};

export default useApi;
