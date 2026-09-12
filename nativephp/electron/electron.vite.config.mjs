import { join } from 'path';
import { defineConfig, externalizeDepsPlugin } from 'electron-vite';

export default defineConfig({
    main: {
        define: {
            'import.meta.env.MAIN_VITE_NATIVEPHP_BUILD_PATH': JSON.stringify('../../../build')
        },
        build: {
            rollupOptions: {
                plugins: [
                    {
                        name: 'watch-external',
                        buildStart() {
                            this.addWatchFile(join(process.env.APP_PATH, 'app', 'Providers', 'NativeAppServiceProvider.php'));
                        }
                    }
                ]
            },
        },
        plugins: [externalizeDepsPlugin()]
    }
});
