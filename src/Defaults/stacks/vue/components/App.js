import { ref } from 'vue/dist/vue.esm-bundler.js';

export default {
    setup() {
        const count = ref(0);
        return { count };
    },
    template: `
        <div>
            <h1>👨‍🍳 Cooker + Vue</h1>
            <p>Edit <code>resources/js/components/App.js</code> to get started.</p>
            <button @click="count++">Count is {{ count }}</button>
        </div>
    `,
};
