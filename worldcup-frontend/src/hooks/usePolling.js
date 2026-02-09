import { useState, useEffect, useCallback, useRef } from 'react';

const POLLING_INTERVAL = 5000; // 5 secondes

export const usePolling = (fetchFunction, interval = POLLING_INTERVAL) => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const intervalRef = useRef(null);
  const fetchFunctionRef = useRef(fetchFunction);

  // Update ref when fetchFunction changes
  useEffect(() => {
    fetchFunctionRef.current = fetchFunction;
  }, [fetchFunction]);

  const fetchData = useCallback(async () => {
    try {
      const response = await fetchFunctionRef.current();
      setData(response.data);
      setError(null);
    } catch (err) {
      setError(err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchData();
    intervalRef.current = setInterval(fetchData, interval);

    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
        intervalRef.current = null;
      }
    };
  }, [fetchData, interval]);

  return { data, loading, error, refetch: fetchData };
};

export default usePolling;
