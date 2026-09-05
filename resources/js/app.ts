import '../css/app.css'

import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import type { DefineComponent } from 'vue'
import { createApp, h } from 'vue'
import { ZiggyVue } from 'ziggy-js'
import { initializeTheme } from './composables/useAppearance'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel'


/*
|--------------------------------------------------------------------------
| Custom Alert & Confirm Dialog
|--------------------------------------------------------------------------
*/

function createDialog(message: string, withCancel: boolean = false): Promise<boolean> {
    return new Promise((resolve) => {

        const overlay = document.createElement("div")
        overlay.style.cssText = `
            position:fixed;
            inset:0;
            background:rgba(0,0,0,.35);
            display:flex;
            align-items:center;
            justify-content:center;
            z-index:999999;
            font-family:system-ui;
        `

        const modal = document.createElement("div")
        modal.style.cssText = `
            background:white;
            padding:24px;
            border-radius:12px;
            width:340px;
            box-shadow:0 10px 30px rgba(0,0,0,.2);
            text-align:center;
            animation:alertPop .18s ease;
        `

        modal.innerHTML = `
            <div style="font-size:18px;font-weight:600;margin-bottom:12px;color:#111">
                Notification
            </div>

            <div style="color:#555;margin-bottom:24px;line-height:1.5">
                ${message}
            </div>

            <div style="display:flex;gap:10px;justify-content:center">
                ${
                    withCancel
                        ? `<button id="dlgCancel"
                            style="
                                background:#e5e7eb;
                                border:none;
                                padding:8px 18px;
                                border-radius:8px;
                                cursor:pointer;
                            ">Cancel</button>`
                        : ""
                }

                <button id="dlgOk"
                    style="
                        background:#2563eb;
                        color:white;
                        border:none;
                        padding:8px 18px;
                        border-radius:8px;
                        cursor:pointer;
                        font-weight:500;
                    ">
                    OK
                </button>
            </div>
        `

        overlay.appendChild(modal)
        document.body.appendChild(overlay)

        const close = (result: boolean) => {
            overlay.remove()
            document.removeEventListener("keydown", escHandler)
            resolve(result)
        }

        const okBtn = modal.querySelector("#dlgOk") as HTMLButtonElement | null
        const cancelBtn = modal.querySelector("#dlgCancel") as HTMLButtonElement | null

        okBtn?.addEventListener("click", () => close(true))
        cancelBtn?.addEventListener("click", () => close(false))

        const escHandler = (e: KeyboardEvent) => {
            if (e.key === "Escape") {
                close(false)
            }
        }

        document.addEventListener("keydown", escHandler)

    })
}


// override alert
window.alert = function (message: string) {
    createDialog(message, false)
}

// override confirm
window.confirm = function (message: string): Promise<boolean> {
    return createDialog(message, true)
}


// popup animation
const style = document.createElement("style")
style.innerHTML = `
@keyframes alertPop{
    from{transform:scale(.92);opacity:0}
    to{transform:scale(1);opacity:1}
}`
document.head.appendChild(style)


/*
|--------------------------------------------------------------------------
| Inertia App Setup
|--------------------------------------------------------------------------
*/

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),

    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue')
        ),

    setup({ el, App, props, plugin }) {

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el)

    },

    progress: {
        color: '#4B5563',
    },
})


// set theme (light/dark)
initializeTheme()