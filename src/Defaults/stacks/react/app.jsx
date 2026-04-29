import { createRoot } from 'react-dom/client';
import App from './components/App.jsx';

const mount = document.getElementById('app');

if (mount) {
    createRoot(mount).render(<App />);
}
