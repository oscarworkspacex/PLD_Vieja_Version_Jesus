<script setup lang="ts">
// `register` no se importa de Wayfinder: con el registro cerrado (el default) no
// existe esa ruta y el import romperia la compilacion; los enlaces usan la URL literal.
import { dashboard, login } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const showTermsModal = ref(false);

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Rusconi Consulting">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
        <meta
            name="description"
            content="Rusconi Consulting | Plataforma de automatización y analítica en tiempo real."
        />
    </Head>

    <div
        class="relative min-h-screen overflow-hidden bg-[#05060A] px-6 py-8 text-slate-50 lg:px-10 lg:py-12"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(19,210,255,0.16),transparent_35%),radial-gradient(circle_at_80%_0%,rgba(150,80,255,0.22),transparent_30%),radial-gradient(circle_at_70%_60%,rgba(21,255,164,0.18),transparent_25%)]"
        />
        <div
            class="pointer-events-none absolute left-1/2 top-24 h-72 w-72 -translate-x-1/2 rounded-full bg-gradient-to-r from-cyan-400/10 via-indigo-400/10 to-emerald-400/10 blur-3xl"
        />

        <div class="relative z-10 mx-auto flex max-w-6xl flex-col gap-10">
            <header class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-lg font-semibold tracking-tight shadow-lg shadow-cyan-400/10"
                    >
                        RC
                    </div>
                    <div class="flex items-center gap-4">
                        <div>
                            <p class="text-sm uppercase tracking-[0.2em] text-slate-300">
                                Intellilaw AI
                            </p>
                            <p class="text-base font-semibold text-white">
                                Plataforma para la auditoria de SOFOMES
                            </p>
                        </div>
                        <div class="flex items-center gap-2 border-l border-white/10 pl-4">
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg border border-white/10 bg-white/5 overflow-hidden">
                                <img 
                                    src="/images/ImgLiquidez.jpg" 
                                    alt="Liquidez Logo" 
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-white/90">
                                    S.A.P.I. de C.V. SOFOM E.N.R
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <nav class="flex items-center gap-2 text-sm">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                        class="rounded-full bg-white/10 px-4 py-2 font-medium text-white backdrop-blur hover:bg-white/20 transition"
                >
                        Ir a la plataforma
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                            class="rounded-full border border-white/15 px-4 py-2 font-medium text-white/90 backdrop-blur hover:border-cyan-300/50 hover:text-white transition"
                    >
                            Iniciar sesión
                    </Link>
                    <Link
                        v-if="canRegister"
                        href="/register"
                            class="rounded-full bg-gradient-to-r from-cyan-400 via-blue-500 to-indigo-500 px-4 py-2 font-semibold text-white shadow-lg shadow-cyan-500/25 transition hover:brightness-105"
                    >
                            Crear cuenta
                    </Link>
                </template>
            </nav>
        </header>

            <main
                class="grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-start"
            >
                <section class="space-y-7">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-cyan-200/80 ring-1 ring-white/10">
                        Novedad 2025 · Orquestación inteligente
                    </div>

                    <div class="space-y-4">
                        <h1 class="text-4xl font-bold leading-tight text-white sm:text-5xl">
                            Sistema Automátizado en Prevención de Lavado de Dinero y Financiamiento al Terrorismo
                        </h1>
                        <p class="max-w-2xl text-lg text-slate-200/90">
                            Sistema para auditoria y control de compliance en prevención de lavado de dinero y financiamiento al terrorismo.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link
                            :href="$page.props.auth.user ? dashboard() : login()"
                            class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-emerald-400 via-cyan-400 to-blue-500 px-5 py-3 text-sm font-semibold text-slate-900 shadow-xl shadow-emerald-400/25 transition hover:brightness-110"
                        >
                            Entrar a la plataforma
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="h-4 w-4"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"
                                />
                            </svg>
                        </Link>
                        <Link
                            v-if="canRegister"
                            href="/register"
                            class="inline-flex items-center gap-2 rounded-full border border-white/15 px-5 py-3 text-sm font-semibold text-white/90 backdrop-blur transition hover:border-white/30 hover:text-white"
                        >
                            Agendar demo guiada
                        </Link>
                        <span class="text-sm text-slate-300/80">
                            SLA 99.9% · Data lineage · Seguridad Zero-Trust
                        </span>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-lg shadow-indigo-500/10 backdrop-blur">
                            <p class="text-xs uppercase tracking-wide text-cyan-200/80">
                                Integraciones vivas
                            </p>
                            <p class="mt-2 text-3xl font-bold text-white">+120</p>
                            <p class="text-sm text-slate-300/80">Conectores listos para usar</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-lg shadow-emerald-500/10 backdrop-blur">
                            <p class="text-xs uppercase tracking-wide text-emerald-200/80">
                                Respuesta en tiempo real
                            </p>
                            <p class="mt-2 text-3xl font-bold text-white">0.4s</p>
                            <p class="text-sm text-slate-300/80">Latencia promedio en flujos críticos</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-lg shadow-cyan-500/10 backdrop-blur">
                            <p class="text-xs uppercase tracking-wide text-indigo-200/80">
                                Ahorro anual
                            </p>
                            <p class="mt-2 text-3xl font-bold text-white">18%</p>
                            <p class="text-sm text-slate-300/80">Optimización operativa comprobada</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 text-xs uppercase tracking-[0.2em] text-slate-300/70">
                        <span class="rounded-full bg-white/5 px-3 py-1 ring-1 ring-white/10">
                            Observabilidad
                        </span>
                        <span class="rounded-full bg-white/5 px-3 py-1 ring-1 ring-white/10">
                            IA explicable
                        </span>
                        <span class="rounded-full bg-white/5 px-3 py-1 ring-1 ring-white/10">
                            Compliance-ready
                                </span>
                        <span class="rounded-full bg-white/5 px-3 py-1 ring-1 ring-white/10">
                            Equipos alineados
                            </span>
                    </div>
                </section>

                <section
                    class="relative overflow-hidden rounded-3xl border border-white/10 bg-white/5 p-6 shadow-2xl shadow-cyan-500/20 backdrop-blur"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300/70">
                                Panel en vivo
                            </p>
                            <p class="text-lg font-semibold text-white">
                                Flujo de decisiones Rusconi OS
                            </p>
                        </div>
                        <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-semibold text-emerald-200 ring-1 ring-emerald-300/40">
                            Operando
                        </span>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                            <div class="flex items-center gap-2 text-sm text-emerald-100">
                                <span class="h-2 w-2 rounded-full bg-emerald-400" />
                                Latido de eventos
                            </div>
                            <p class="mt-3 text-4xl font-semibold text-white">12.3k</p>
                            <p class="text-sm text-slate-300/80">eventos procesados / minuto</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                            <div class="flex items-center gap-2 text-sm text-cyan-100">
                                <span class="h-2 w-2 rounded-full bg-cyan-400" />
                                Modelos activos
                            </div>
                            <p class="mt-3 text-4xl font-semibold text-white">34</p>
                            <p class="text-sm text-slate-300/80">rutas de decisión supervisadas</p>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        <div class="flex items-center justify-between rounded-2xl border border-white/5 bg-white/5 p-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-cyan-400/30 to-indigo-500/30 text-white">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="h-5 w-5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M9 9l10.5-6M9 9v12m0-12L3 6m6 15l10.5-6m0 0V3M19.5 15L14 12"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-white">
                                        Orquestador financiero
                                    </p>
                                    <p class="text-xs text-slate-300/80">
                                        Conciliación automática · Cashflow diario
                                    </p>
                                </div>
                            </div>
                            <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-semibold text-emerald-200 ring-1 ring-emerald-300/40">
                                99.97% ok
                            </span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-white/5 bg-white/5 p-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-amber-400/30 to-orange-500/30 text-white">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        class="h-5 w-5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M4.5 4.5l15 15m0-15l-15 15"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-white">
                                        Alertas de riesgo
                                    </p>
                                    <p class="text-xs text-slate-300/80">
                                        IA explicable · Mitigación autónoma
                                    </p>
                                </div>
                            </div>
                            <span class="rounded-full bg-amber-400/15 px-3 py-1 text-xs font-semibold text-amber-200 ring-1 ring-amber-300/40">
                                3 atenciones
                            </span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-white/5 bg-white/5 p-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400/30 to-teal-500/30 text-white">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        class="h-5 w-5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M3 13.5l6.75 6.75L21 4.5"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-white">
                                        Workflows aprobados
                                    </p>
                                    <p class="text-xs text-slate-300/80">
                                        52 pipelines listos · 8 en revisión
                                    </p>
                                </div>
                            </div>
                            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white/80 ring-1 ring-white/15">
                                Gobernanza activa
                            </span>
                        </div>
                    </div>
                </section>
            </main>

            <section class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-3xl border border-white/10 bg-white/5 p-6 shadow-lg shadow-indigo-500/10 backdrop-blur">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-400/25 text-indigo-50">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="h-5 w-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 7.5h18m-18 9h18M9.75 3v18m4.5-18v18"
                                />
                            </svg>
                        </span>
                        <h3 class="text-lg font-semibold text-white">
                            Sistema actualizado lo más reciente
                        </h3>
                    </div>
                    <p class="mt-3 text-sm text-slate-300/90">
                        Nuestro sistema se actualiza constantemente para mantenerse al día con las últimas mejoras.
                    </p>
                    <p class="mt-4 text-sm font-semibold text-cyan-200">
                        Tiempo de despliegue: 4x más rápido.
                    </p>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/5 p-6 shadow-lg shadow-emerald-500/10 backdrop-blur">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-400/25 text-emerald-50">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="h-5 w-5"
                    >
                        <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 6v6l4 2"
                        />
                    </svg>
                        </span>
                        <h3 class="text-lg font-semibold text-white">
                            Observabilidad total
                        </h3>
                    </div>
                    <p class="mt-3 text-sm text-slate-300/90">
                        Trazabilidad completa: de la señal al impacto financiero, con auditoría lista para compliance.
                    </p>
                    <p class="mt-4 text-sm font-semibold text-emerald-200">
                        MTTR promedio: 11 minutos.
                    </p>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/5 p-6 shadow-lg shadow-cyan-500/10 backdrop-blur">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-cyan-400/25 text-cyan-50">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="h-5 w-5"
                        >
                            <path
                                    stroke-linecap="round"
                                stroke-linejoin="round"
                                    d="M4.5 12.75l6 6 9-13.5"
                                />
                            </svg>
                        </span>
                        <h3 class="text-lg font-semibold text-white">
                            IA operativa segura
                        </h3>
                    </div>
                    <p class="mt-3 text-sm text-slate-300/90">
                        Modelos gobernados, explicables y alineados a políticas; sin riesgos de datos sensibles.
                    </p>
                    <p class="mt-4 text-sm font-semibold text-cyan-200">
                        Predicciones con 92% de precisión validada.
                    </p>
                </div>
            </section>

            <section
                class="flex flex-col gap-6 rounded-3xl border border-white/10 bg-white/5 p-6 shadow-xl shadow-cyan-500/15 backdrop-blur lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="space-y-2">
                    <p class="text-xs uppercase tracking-[0.25em] text-slate-300/70">
                        Próximo paso
                    </p>
                    <h3 class="text-2xl font-semibold text-white">
                        Terminos y condiciones
                    </h3>
                    <p class="text-sm text-slate-300/85">
                        Por favor, revisa nuestros términos y condiciones antes de usar nuestra plataforma.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        @click="showTermsModal = true"
                        class="rounded-full bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-lg shadow-white/20 transition hover:translate-y-[-1px]"
                    >
                        Revisar términos y condiciones
                    </button>
                    <Link
                        v-if="canRegister"
                        href="/register"
                        class="rounded-full border border-white/20 px-5 py-3 text-sm font-semibold text-white/90 backdrop-blur transition hover:border-white/35"
                    >
                        Soporte técnico
                    </Link>
                </div>
            </section>
        </div>
    </div>

    <div
        v-if="showTermsModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
        @click.self="showTermsModal = false"
    >
        <div
            class="max-h-[90vh] w-full max-w-5xl overflow-y-auto rounded-2xl border border-white/10 bg-[#0B1020] p-6 text-slate-200 shadow-2xl"
        >
            <div class="mb-4 flex items-start justify-between gap-4">
                <h3 class="text-xl font-semibold text-white">
                    Términos y Condiciones y Política de Privacidad
                </h3>
                <button
                    type="button"
                    class="rounded-md border border-white/20 px-3 py-1 text-sm text-white hover:bg-white/10"
                    @click="showTermsModal = false"
                >
                    Cerrar
                </button>
            </div>

            <div class="space-y-5 text-sm leading-6 text-slate-300">
                <div class="space-y-1">
                    <p class="text-base font-semibold text-white">INTELLILAW MEXICO, S.A. DE C.V.</p>
                    <p>(INTELLILAW MEXICO, SOCIEDAD ANÓNIMA DE CAPITAL VARIABLE)</p>
                </div>

                <p>
                    En INTELLILAW MEXICO, S.A. de C.V., empresa conocida comercialmente como IntelliLaw Mexico,
                    con domicilio en __________________________________________________________, nos comprometemos
                    a proteger la privacidad de tus datos personales y a tratarlos con la máxima confidencialidad.
                    Esta Política de Privacidad establece los términos en los que recopilamos, usamos, almacenamos,
                    divulgamos y protegemos tus datos personales conforme a la Ley Federal de Protección de Datos
                    Personales en Posesión de los Particulares (LFPDPPP) y su Reglamento, así como cualquier otra
                    legislación aplicable en materia de protección de datos.
                </p>

                <div>
                    <h4 class="mb-2 text-base font-semibold text-white">Datos Personales</h4>
                    <p class="mb-2">La Empresa recopila los siguientes datos personales para cumplir con los fines establecidos en esta política:</p>
                    <ul class="list-disc space-y-1 pl-5">
                        <li>Datos de identificación personal: Nombre completo, dirección, teléfono, correo electrónico, entre otros.</li>
                        <li>Datos financieros: Información sobre cuentas bancarias, transacciones, historial de pagos, ingresos y balances de cuentas.</li>
                        <li>Datos de contacto: Información relacionada con comunicaciones electrónicas, como el correo electrónico.</li>
                        <li>Datos de uso: Información sobre el uso del sistema (direcciones IP, tipo de dispositivo, ubicación, registros de acceso, etc.).</li>
                        <li>Datos sensibles (cuando aplicable): Datos financieros relacionados con la administración de la cartera, historial crediticio, entre otros.</li>
                    </ul>
                </div>

                <div>
                    <h4 class="mb-2 text-base font-semibold text-white">Finalidad del Tratamiento de los Datos Personales</h4>
                    <p class="mb-2">Los datos personales que recopilamos se utilizan para los siguientes fines:</p>
                    <ul class="list-disc space-y-1 pl-5">
                        <li>Cumplimiento de obligaciones contractuales: Para la administración y operación del sistema financiero, gestión de cartera, PLD y para brindarte el servicio que has solicitado.</li>
                        <li>Mejorar la calidad de nuestros servicios: Para ofrecer soporte al cliente, responder a dudas, realizar actualizaciones del sistema y mejorar la experiencia de usuario.</li>
                        <li>Cumplimiento de obligaciones legales: Para cumplir con las disposiciones fiscales y legales aplicables, así como con los requerimientos de autoridades competentes.</li>
                        <li>Seguridad: Para prevenir fraudes, proteger nuestra infraestructura tecnológica y mantener la integridad de los datos almacenados.</li>
                        <li>Publicidad y promoción: Si así lo autorizas, para enviarte ofertas y promociones relacionadas con nuestros servicios.</li>
                    </ul>
                </div>

                <div>
                    <h4 class="mb-2 text-base font-semibold text-white">Bases Legales para el Tratamiento de Datos</h4>
                    <p class="mb-2">El tratamiento de tus datos personales se basa en las siguientes bases legales, conforme a la LFPDPPP:</p>
                    <ul class="list-disc space-y-1 pl-5">
                        <li>Consentimiento: Al proporcionarnos tus datos personales, aceptas y consientes el tratamiento de los mismos según los fines establecidos.</li>
                        <li>Cumplimiento de una obligación contractual: En los casos en los que el tratamiento de datos es necesario para la ejecución de un contrato celebrado contigo.</li>
                        <li>Cumplimiento de obligaciones legales: Cuando estemos obligados a procesar tus datos conforme a la ley.</li>
                    </ul>
                </div>

                <div>
                    <h4 class="mb-2 text-base font-semibold text-white">Derechos de los Titulares de los Datos Personales</h4>
                    <p class="mb-2">En todo momento, tendrás los siguientes derechos en relación con tus datos personales, que podrás ejercer ante la Empresa:</p>
                    <ul class="list-disc space-y-1 pl-5">
                        <li>Acceso: Derecho a conocer qué datos personales tenemos sobre ti, así como las condiciones y tratamientos que se les da.</li>
                        <li>Rectificación: Derecho a corregir datos personales cuando sean incorrectos o incompletos.</li>
                        <li>Cancelación: Derecho a solicitar la eliminación de tus datos cuando consideres que ya no son necesarios o que están siendo utilizados de manera inapropiada.</li>
                        <li>Oposición: Derecho a oponerte al tratamiento de tus datos personales en situaciones específicas.</li>
                        <li>Portabilidad (en su caso, cuando resulte aplicable): Derecho a recibir tus datos en un formato estructurado, comúnmente utilizado y de fácil lectura, para ser transferidos a otro responsable.</li>
                    </ul>
                    <p class="mt-2">
                        Para ejercer cualquiera de estos derechos, deberás presentar una solicitud ante la Empresa a través de los medios establecidos en la sección de contacto de esta política.
                    </p>
                </div>

                <div>
                    <h4 class="mb-2 text-base font-semibold text-white">Revocación del Consentimiento</h4>
                    <p>
                        En cualquier momento, podrás revocar el consentimiento otorgado para el tratamiento de tus datos personales.
                        Para hacerlo, deberás enviar una solicitud a través de los canales de contacto proporcionados en esta política.
                        Sin embargo, ten en cuenta que la revocación de tu consentimiento no afectará la legalidad del tratamiento realizado con anterioridad.
                    </p>
                </div>

                <div>
                    <h4 class="mb-2 text-base font-semibold text-white">Transferencia de Datos Personales</h4>
                    <p class="mb-2">La Empresa podrá transferir tus datos personales a terceros, dentro y fuera del territorio nacional, en los siguientes casos:</p>
                    <ul class="list-disc space-y-1 pl-5">
                        <li>Proveedores de servicios: Con empresas que nos ayuden a operar el sistema, prestar servicios de soporte o realizar análisis de datos. Estas empresas estarán obligadas a cumplir con las mismas medidas de protección de datos que nosotros.</li>
                        <li>Cumplimiento de obligaciones legales: Cuando la transferencia sea necesaria para cumplir con obligaciones legales o cuando lo soliciten autoridades competentes.</li>
                        <li>En casos de transacciones corporativas: En caso de fusión, adquisición o venta de la Empresa, los datos serán transferidos a la nueva entidad.</li>
                    </ul>
                </div>

                <div>
                    <h4 class="mb-2 text-base font-semibold text-white">Medidas de Seguridad</h4>
                    <p>
                        Implementamos medidas de seguridad administrativas, técnicas y físicas para proteger tus datos personales contra pérdida, uso indebido, acceso no autorizado o divulgación.
                        Estas medidas se revisan y actualizan periódicamente para garantizar la máxima protección posible.
                    </p>
                    <p class="mt-2">
                        Asimismo, podrás revocar o retirar el consentimiento proporcionado previamente para el tratamiento de tus datos personales u oponerte a dicho tratamiento.
                        Es importante tener en cuenta que estos derechos pueden no ser absolutos.
                        Por ejemplo, si retiras tu consentimiento para procesar tus datos personales, podremos continuar procesando tus datos personales en la medida requerida o permitida por la ley,
                        en particular en relación con el ejercicio y la defensa de nuestros derechos o nuestras obligaciones legales y/o regulatorias.
                    </p>
                </div>

                <div>
                    <h4 class="mb-2 text-base font-semibold text-white">Uso de Cookies</h4>
                    <p>
                        Utilizamos cookies y tecnologías similares para mejorar la experiencia del usuario en nuestro sistema, analizar el uso de los servicios y personalizar las interacciones.
                        Puedes configurar tu navegador para rechazar el uso de cookies, pero esto podría afectar el funcionamiento de algunas partes del sistema.
                    </p>
                </div>

                <div>
                    <h4 class="mb-2 text-base font-semibold text-white">Modificaciones a esta Política de Privacidad</h4>
                    <p>
                        INTELLILAW MEXICO, S.A. de C.V. podrá modificar esta política de privacidad en cualquier momento.
                        Las modificaciones serán publicadas en este sitio web y entrarán en vigor desde su publicación.
                        Te recomendamos revisar periódicamente esta política para estar al tanto de cualquier cambio.
                    </p>
                </div>

                <div>
                    <h4 class="mb-2 text-base font-semibold text-white">Contacto</h4>
                    <p>Si tienes dudas, comentarios o solicitudes respecto a esta política de privacidad, o deseas ejercer tus derechos, puedes ponerte en contacto con nosotros a través de los siguientes medios:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <li>Correo electrónico: ________________________________</li>
                        <li>Teléfono: _________________________________________</li>
                        <li>Dirección física: ______________________________________________________________</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
