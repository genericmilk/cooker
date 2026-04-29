import { useState } from 'react';

export default function App() {
    const [count, setCount] = useState(0);

    return (
        <div>
            <h1>👨‍🍳 Cooker + React</h1>
            <p>Edit <code>resources/js/components/App.jsx</code> to get started.</p>
            <button onClick={() => setCount(count + 1)}>Count is {count}</button>
        </div>
    );
}
