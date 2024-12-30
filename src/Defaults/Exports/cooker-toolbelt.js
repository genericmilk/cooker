const cookerToolbelt = {
    name: 'Cooker Toolbelt',
    version: '1.0.0',
    description: 'The assistant for the Cooker framework',
    isDebug: true,
    console: {
        log: (message) => {
            if (cookerToolbelt.isDebug) {
                console.log("%c👨‍🍳 Cooker", "color: black; background-color:rgb(213, 213, 213);", message);
            }
        },
        info: (message) => {
            if (cookerToolbelt.isDebug) {
                console.info("%c👨‍🍳 Cooker", "color: white; background-color: #007acc;", message);
            }
        },
        warn: (message) => {
            if (cookerToolbelt.isDebug) {
                console.warn(message);
                console.warn("%c👨‍🍳 Cooker", "color: white; background-color:rgb(255, 153, 0);", message);
            }
        },
        error: (message) => {
            if (cookerToolbelt.isDebug) {
                console.error("%c👨‍🍳 Cooker", "color: white; background-color:rgb(255, 153, 0);", message);
            }
        }
    },
};

export default cookerToolbelt;