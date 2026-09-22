<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch, getCurrentInstance } from 'vue';
import axios from 'axios';
import admin from '@/routes/admin';

// Get numeralFormat from global properties
const instance = getCurrentInstance();
const numeralFormat = instance?.appContext.config.globalProperties.numeralFormat || ((value: number) => value.toLocaleString());

// Importar componentes modulares
import MedioContacto from '@/components/ClientCapture/MedioContacto.vue';
import DatosIdentificacion from '@/components/ClientCapture/DatosIdentificacion.vue';
import DatosLaborales from '@/components/ClientCapture/DatosLaborales.vue';
import SolicitudOperacion from '@/components/ClientCapture/SolicitudOperacion.vue';
import DatosContacto from '@/components/ClientCapture/DatosContacto.vue';
import Garantias from '@/components/ClientCapture/Garantias.vue';
import PLD from '@/components/ClientCapture/PLD.vue';

// Reactive state for the search
const searchQuery = ref('');
const data = ref([]);
const current = ref(1);
const total = ref(0);
const pageSize = ref(50);
const searchListaNegraAlerta = ref<{ count: number; visible: boolean }>({ count: 0, visible: false });
let searchListaNegraAlertoTimeout: ReturnType<typeof setTimeout> | null = null;
const page = usePage();
const isSaving = ref(false);
const isLoadingCaptureForEdit = ref(false);
const isLoadingSelectorClients = ref(false);
const isUploadingAnexoByClient = ref<Record<number, boolean>>({});
const isDeletingClientById = ref<Record<number, boolean>>({});
const anexoFilesByClient = ref<Record<number, File | null>>({});
const anexoInputRefs = ref<Record<number, HTMLInputElement | null>>({});
const selectedValidationByClient = ref<Record<number, '' | 'SI' | 'NO'>>({});
const dirtyValidationByClient = ref<Record<number, boolean>>({});
const isSavingValidations = ref(false);
const selectedCaptureOption = ref('solicitud-p-fisica');
const editingClientId = ref<number | null>(null);
const selectorSearch = ref('');
const prestamoSearch = ref('');
const systemUserSearch = ref('');
const isLoadingSystemUsers = ref(false);
const isLoadingPrestamos = ref(false);
const isUpdatingPrestamoById = ref<Record<number, boolean>>({});
const isSavingSystemUser = ref(false);
const isSavingSystemUserPassword = ref(false);
const isSystemUserModalOpen = ref(false);
const isSystemUserPasswordModalOpen = ref(false);
const systemUserModalMode = ref<'create' | 'edit'>('create');
const editingSystemUserId = ref<number | null>(null);
const passwordTargetUser = ref<{ id: number; name: string } | null>(null);
const selectorClients = ref<Array<{
    numero_cliente: number;
    cliente: string;
    rfc: string;
    tipo_persona: string;
    anexo_disponible: boolean;
    anexo_nombre: string | null;
    anexo_total: number;
    cliente_validado: 'SI' | 'NO' | null;
}>>([]);
const prestamos = ref<Array<{
    numero_prestamo: number;
    nombre_cliente: string;
    tipo_solicitud: string | null;
    monto_solicitado: number | string | null;
    estatus_prestamo: 'PENDIENTE' | 'ACEPTADO' | 'RECHAZADO';
}>>([]);
const systemUsers = ref<Array<{
    id: number;
    name: string;
    email: string;
    status: number;
    role: string | null;
}>>([]);
const systemRoles = ref<string[]>([]);
const systemUserForm = ref({
    name: '',
    email: '',
    status: 1,
    role: 'customer',
    password: '',
    password_confirmation: '',
});
const systemUserPasswordForm = ref({
    password: '',
    password_confirmation: '',
});
const isAnexoModalOpen = ref(false);
const isLoadingClientAnexos = ref(false);
const selectedClientForAnexos = ref<{
    numero_cliente: number;
    cliente: string;
} | null>(null);
const selectedClientAnexos = ref<Array<{
    id: number | null;
    nombre: string;
    mime_type: string | null;
    size_bytes: number | null;
    fecha_carga: string | null;
    download_url: string;
}>>([]);
const captureOptions = [
    { label: 'Solicitud P. Física', value: 'solicitud-p-fisica' },
    { label: 'Solicitud P. Física A.E', value: 'solicitud-p-fisica-ae' },
    { label: 'Solicitud P. Moral', value: 'solicitud-p-moral' },
];

const getDefaultFormData = () => ({
    medioContacto: {},
    datosIdentificacion: {},
    datosLaborales: {},
    solicitudOperacion: {},
    datosContacto: {},
    garantias: {},
    pld: {},
});

const goToSection = (
    section: 'captura-cliente' | 'seleccionar-clientes' | 'prestamos' | 'umbral' | 'clasificacion-riesgo' | 'alertas' | 'alertas-anonimas' | 'usuarios-sistema',
    extraParams: Record<string, string> = {}
) => {
    const params = new URLSearchParams({ section, ...extraParams });
    window.location.href = `/admin/dashboard?${params.toString()}`;
};

const readEditClientIdFromUrl = (): number | null => {
    const [, queryString = ''] = page.url.split('?');
    const params = new URLSearchParams(queryString);
    const rawId = params.get('editClient');

    if (!rawId) {
        return null;
    }

    const parsedId = Number.parseInt(rawId, 10);
    return Number.isNaN(parsedId) ? null : parsedId;
};

const resetCaptureForm = () => {
    selectedCaptureOption.value = 'solicitud-p-fisica';
    formData.value = getDefaultFormData();
    activeTab.value = 'medio-contacto';
    editingClientId.value = null;
};

// Pestaña activa
const activeTab = ref('medio-contacto');

// Datos del formulario
const formData = ref(getDefaultFormData());

const currentSection = computed(() => {
    const [, queryString = ''] = page.url.split('?');
    const params = new URLSearchParams(queryString);
    return params.get('section');
});

const isCapturaClienteView = computed(() => {
    return currentSection.value === 'captura-cliente';
});

const isSeleccionarClientesView = computed(() => currentSection.value === 'seleccionar-clientes');
const isPrestamosView = computed(() => currentSection.value === 'prestamos');
const isUmbralView = computed(() => currentSection.value === 'umbral');
const isClasificacionRiesgoView = computed(() => currentSection.value === 'clasificacion-riesgo');
const isAlertasView = computed(() => currentSection.value === 'alertas');
const isAlertasAnonimasView = computed(() => currentSection.value === 'alertas-anonimas');
const isSystemUsersView = computed(() => currentSection.value === 'usuarios-sistema');

const riskTabs = [
    { id: 'indicadores-generales', label: 'Indicadores generales' },
    { id: 'detalle-categorias', label: 'Detalle de categorias' },
] as const;
const activeRiskTab = ref<'indicadores-generales' | 'detalle-categorias'>('indicadores-generales');

const umbralTabs = [
    { id: 'saldo', label: 'Saldo' },
    { id: 'estados', label: 'Estados' },
] as const;
const activeUmbralTab = ref<'saldo' | 'estados'>('saldo');

const umbralPersonaFisica = [
    {
        rango: '0 a 300,000',
        riesgo: 'Riesgo bajo',
        from: 0,
        to: 300000,
        color: 'bg-sky-300',
        badge: 'text-sky-700 bg-sky-100 dark:text-sky-300 dark:bg-sky-900/30',
    },
    {
        rango: '300,000 a 600,000',
        riesgo: 'Alto riesgo',
        from: 300000,
        to: 600000,
        color: 'bg-amber-300',
        badge: 'text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30',
    },
] as const;

const umbralPersonaMoralFideicomiso = [
    {
        rango: '0 a 500,000',
        riesgo: 'Riesgo bajo',
        from: 0,
        to: 500000,
        color: 'bg-emerald-500',
        badge: 'text-emerald-700 bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-900/30',
    },
    {
        rango: '500,000 a 600,000',
        riesgo: 'Alto riesgo',
        from: 500000,
        to: 600000,
        color: 'bg-red-500',
        badge: 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/30',
    },
] as const;

const getRangeWidth = (from: number, to: number, total: number): string => {
    const range = Math.max(to - from, 0);
    return `${Math.max((range / total) * 100, 8)}%`;
};

const parseMontoSolicitado = (monto: number | string | null | undefined): number => {
    if (typeof monto === 'number') {
        return Number.isFinite(monto) ? monto : 0;
    }

    if (typeof monto === 'string') {
        const cleaned = monto.replace(/[^0-9.]/g, '');
        const parsed = Number.parseFloat(cleaned);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    return 0;
};

const isPersonaMoralSolicitud = (tipoSolicitud: string | null | undefined): boolean => {
    return String(tipoSolicitud ?? '').toLowerCase().includes('moral');
};

const getMontoRiesgoMeta = (
    tipoSolicitud: string | null | undefined,
    monto: number | string | null | undefined
) => {
    const montoNumerico = parseMontoSolicitado(monto);
    const personaMoral = isPersonaMoralSolicitud(tipoSolicitud);

    if (personaMoral) {
        if (montoNumerico >= 500000) {
            return {
                label: 'ALTO',
                textClass: 'text-red-700 dark:text-red-300',
                bgClass: 'bg-red-100 dark:bg-red-900/30',
            };
        }

        return {
            label: 'BAJO',
            textClass: 'text-emerald-700 dark:text-emerald-300',
            bgClass: 'bg-emerald-100 dark:bg-emerald-900/30',
        };
    }

    if (montoNumerico >= 300000) {
        return {
            label: 'ALTO',
            textClass: 'text-amber-700 dark:text-amber-300',
            bgClass: 'bg-amber-100 dark:bg-amber-900/30',
        };
    }

    return {
        label: 'BAJO',
        textClass: 'text-sky-700 dark:text-sky-300',
        bgClass: 'bg-sky-100 dark:bg-sky-900/30',
    };
};

const readRiskClientIdFromUrl = (): number | null => {
    const [, queryString = ''] = page.url.split('?');
    const params = new URLSearchParams(queryString);
    const rawId = params.get('riskClient');

    if (!rawId) {
        return null;
    }

    const parsedId = Number.parseInt(rawId, 10);
    return Number.isNaN(parsedId) ? null : parsedId;
};
const selectedRiskClientId = ref<number | null>(readRiskClientIdFromUrl());

const selectedRiskClient = computed(() => {
    if (!selectedRiskClientId.value) {
        return null;
    }

    return selectorClients.value.find((client) => client.numero_cliente === selectedRiskClientId.value) ?? null;
});

const alertasVerticalTab = ref<'operaciones-24h' | 'operaciones-internas-preocupantes' | 'operaciones-inusuales' | 'operaciones-internas-comportamiento' | 'operaciones-relevantes' | 'listas-negras-pep' | 'bitacora-vigilancia-estricta' | 'monitor-riesgo' | 'monitor-riesgo-paises' | 'descargar-xml'>('operaciones-24h');

// -- Alertas Lista Negra / PEP --
const alertasListaNegra = ref<Array<{
    id: number;
    excel_name_origen: string;
    valor_detectado: string;
    row_data: string[] | null;
    nombre_lista: string;
    is_leida: boolean;
    leida_at: string | null;
    created_at: string;
}>>([]);
const isLoadingListaNegra = ref(false);
const isMarkingListaNegraById = ref<Record<number, boolean>>({});
const isDeletingListaNegraById = ref<Record<number, boolean>>({});

const alertasListaNegraNoLeidas = computed(() =>
    alertasListaNegra.value.filter((a) => !a.is_leida).length
);

const loadAlertasListaNegra = async () => {
    isLoadingListaNegra.value = true;
    try {
        const response = await axios.get('/admin/alertas-lista-negra');
        alertasListaNegra.value = response.data?.data ?? [];
    } catch (error) {
        console.error('Error al cargar alertas de lista negra:', error);
    } finally {
        isLoadingListaNegra.value = false;
    }
};

const markListaNegraAsRead = async (alertaId: number) => {
    if (isMarkingListaNegraById.value[alertaId]) return;
    isMarkingListaNegraById.value[alertaId] = true;
    try {
        const response = await axios.patch(`/admin/alertas-lista-negra/${alertaId}/leida`);
        const updated = response.data?.data;
        if (updated) {
            alertasListaNegra.value = alertasListaNegra.value.map((a) =>
                a.id === alertaId ? updated : a
            );
        }
    } catch (error) {
        console.error('Error al marcar alerta como leída:', error);
    } finally {
        isMarkingListaNegraById.value[alertaId] = false;
    }
};

const deleteAlertaListaNegra = async (alertaId: number) => {
    if (!window.confirm('¿Eliminar esta alerta? Esta acción no se puede deshacer.')) return;
    if (isDeletingListaNegraById.value[alertaId]) return;
    isDeletingListaNegraById.value[alertaId] = true;
    try {
        await axios.delete(`/admin/alertas-lista-negra/${alertaId}`);
        alertasListaNegra.value = alertasListaNegra.value.filter((a) => a.id !== alertaId);
    } catch (error) {
        console.error('Error al eliminar alerta:', error);
    } finally {
        isDeletingListaNegraById.value[alertaId] = false;
    }
};

watch(alertasVerticalTab, (tab) => {
    if (tab === 'listas-negras-pep') {
        loadAlertasListaNegra();
    }
});

const alertasGeneratedLabel = computed(() => {
    return new Intl.DateTimeFormat('es-MX', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date());
});

const alertasSections = [
    {
        id: 'operaciones-24h',
        label: 'Operaciones Inusuales De 24 Hrs.',
        titulo: 'Operaciones Inusuales De 24 Hrs',
        descripcion: 'Permite enviar una OI de 24 horas cuando se cuenta con información basada en indicios o hechos concretos de recursos con posible origen ilícito.',
        bullets: [],
    },
    {
        id: 'operaciones-internas-preocupantes',
        label: 'Operaciones Internas Preocupantes',
        titulo: 'Operaciones Internas Preocupantes',
        descripcion: 'Permite que cualquier empleado pueda reportar una OIP al Oficial de Cumplimiento.',
        bullets: [],
    },
    {
        id: 'operaciones-inusuales',
        label: 'Operaciones Inusuales',
        titulo: 'Operaciones Inusuales',
        descripcion: '',
        bullets: [
            'Pagos superiores al monto máximo diario y acumulado mensual.',
            'Número de pagos superiores a la frecuencia máxima mensual.',
            'Liquidaciones anticipadas de créditos.',
            'Acumulación de pagos en efectivo por importes atípicos en periodos cortos.',
            'Pagos en efectivo por montos mayores al promedio del cliente.',
        ],
    },
    {
        id: 'operaciones-internas-comportamiento',
        label: 'Operaciones Inusuales Comportamiento',
        titulo: 'Operaciones Inusuales Comportamiento',
        descripcion: '',
        bullets: [
            'Clientes que omiten la existencia de un Propietario Real.',
            'Transferencias de terceros no identificados como beneficiario real.',
            'Uso en efectivo sin justificación para el perfil del cliente.',
            'Información reportada inconsistente con ingresos y visitas previas.',
            'Negativa a proporcionar datos o documentación solicitada.',
        ],
    },
    {
        id: 'operaciones-relevantes',
        label: 'Operaciones Relevantes',
        titulo: 'Operaciones Relevantes',
        descripcion: '',
        bullets: [
            'Pagos por 7,500 dólares o más en efectivo o su equivalente en moneda nacional al tipo de cambio del día.',
        ],
    },
    {
        id: 'listas-negras-pep',
        label: 'Listas Negras Y De Personas Políticamente Expuestas',
        titulo: 'Listas Negras Y De Personas Políticamente Expuestas',
        descripcion: 'Permite consultar de forma automática nombres de clientes y empresas en listas negras y de personas políticamente expuestas.',
        bullets: [],
    },
    {
        id: 'bitacora-vigilancia-estricta',
        label: 'Bitácora De Operaciones De Vigilancia Estricta',
        titulo: 'Bitácora De Operaciones De Vigilancia Estricta',
        descripcion: '',
        bullets: [
            'Operaciones en un mes calendario, en efectivo, por montos altos en moneda nacional o extranjera.',
            'Persona Física que en un mes calendario acumula pagos superiores a 300,000 pesos en efectivo.',
            'Persona Moral que en un mes calendario acumula pagos superiores a 500,000 pesos en efectivo.',
        ],
    },
    {
        id: 'monitor-riesgo',
        label: 'Monitor De Riesgo',
        titulo: 'Monitor De Riesgo',
        descripcion: 'Evalúa la información del cliente y pondera su nivel de riesgo.',
        bullets: [],
    },
    {
        id: 'monitor-riesgo-paises',
        label: 'Monitor De Riesgo Países',
        titulo: 'Monitor De Riesgo Países',
        descripcion: 'Evalúa la información del país del cliente y pondera su nivel de riesgo.',
        bullets: [],
    },
] as const;

const currentAlertasSection = computed(() => {
    return alertasSections.find((section) => section.id === alertasVerticalTab.value) ?? alertasSections[0];
});

const alertasTab = ref<'buzon' | 'reportes'>('buzon');
const isSavingAlertaAnonima = ref(false);
const isLoadingReportesAnonimos = ref(false);
const isDownloadingXml = ref(false);
const isDownloadingXmlRules = ref(false);
const selectedXmlClientId = ref<number | null>(null);
const alertaAnonimaForm = ref({
    destinatario: '',
    asunto: '',
    empleado_reportado: '',
    motivo: '',
    detalle: '',
    denuncia: '',
});
const reportesAnonimos = ref<Array<{
    id: number;
    destinatario: string;
    asunto: string;
    empleado_reportado: string;
    motivo: string;
    detalle: string;
    denuncia: string;
    is_leida: boolean;
    leida_at: string;
    created_at: string;
}>>([]);
const isMarkingReporteById = ref<Record<number, boolean>>({});
const isDeletingReporteById = ref<Record<number, boolean>>({});

const tabs = [
    { id: 'medio-contacto', label: 'Medio de contacto', component: 'MedioContacto' },
    { id: 'datos-identificacion', label: 'Datos de identificación', component: 'DatosIdentificacion' },
    { id: 'datos-laborales', label: 'Datos laborales', component: 'DatosLaborales' },
    { id: 'solicitud-operacion', label: 'Solicitud de operación', component: 'SolicitudOperacion' },
    { id: 'datos-contacto', label: 'Datos de contacto', component: 'DatosContacto' },
    { id: 'garantias', label: 'Garantías', component: 'Garantias' },
    { id: 'pld', label: 'P.L.D.', component: 'PLD' },
];

const setActiveTab = (tabId: string) => {
    activeTab.value = tabId;
};

const saveForm = async () => {
    if (isSaving.value) {
        return;
    }

    isSaving.value = true;

    try {
        const payload = {
            tipo_solicitud: selectedCaptureOption.value,
            medio_contacto: formData.value.medioContacto,
            datos_identificacion: formData.value.datosIdentificacion,
            datos_laborales: formData.value.datosLaborales,
            solicitud_operacion: formData.value.solicitudOperacion,
            datos_contacto: formData.value.datosContacto,
            garantias: formData.value.garantias,
            pld: formData.value.pld,
        };

        if (editingClientId.value) {
            await axios.patch(`/admin/client-capture/${editingClientId.value}`, payload);
            alert('Cliente actualizado correctamente.');
            goToSection('seleccionar-clientes');
        } else {
            await axios.post('/admin/client-capture', payload);
            alert('Información guardada correctamente.');
            resetCaptureForm();
        }
    } catch (error) {
        console.error('Error al guardar:', error);
        alert('No se pudo guardar la información. Revisa la consola para más detalle.');
    } finally {
        isSaving.value = false;
    }
};

const loadClientForEdit = async (clientId: number) => {
    isLoadingCaptureForEdit.value = true;

    try {
        const response = await axios.get(`/admin/client-capture/${clientId}`);
        const clientData = response.data?.data;

        if (!clientData) {
            throw new Error('Cliente sin datos de edición.');
        }

        editingClientId.value = clientData.id;
        selectedCaptureOption.value = clientData.tipo_solicitud || 'solicitud-p-fisica';
        formData.value = {
            medioContacto: clientData.medio_contacto ?? {},
            datosIdentificacion: clientData.datos_identificacion ?? {},
            datosLaborales: clientData.datos_laborales ?? {},
            solicitudOperacion: clientData.solicitud_operacion ?? {},
            datosContacto: clientData.datos_contacto ?? {},
            garantias: clientData.garantias ?? {},
            pld: clientData.pld ?? {},
        };
        activeTab.value = 'medio-contacto';
    } catch (error) {
        console.error('Error al cargar cliente para editar:', error);
        alert('No se pudo cargar la información del cliente para editar.');
    } finally {
        isLoadingCaptureForEdit.value = false;
    }
};

const loadSelectorClients = async () => {
    if (!isSeleccionarClientesView.value && !isClasificacionRiesgoView.value && !isAlertasView.value) {
        return;
    }

    isLoadingSelectorClients.value = true;

    try {
        const response = await axios.get('/admin/client-capture/selector', {
            params: {
                q: selectorSearch.value.trim(),
            },
        });

        selectorClients.value = response.data?.data ?? [];

        const nextSelectedValidation: Record<number, '' | 'SI' | 'NO'> = {};
        const nextDirtyValidation: Record<number, boolean> = {};

        for (const client of selectorClients.value) {
            nextSelectedValidation[client.numero_cliente] = client.cliente_validado ?? '';
            nextDirtyValidation[client.numero_cliente] = false;
        }

        selectedValidationByClient.value = nextSelectedValidation;
        dirtyValidationByClient.value = nextDirtyValidation;
    } catch (error) {
        console.error('Error al cargar clientes:', error);
    } finally {
        isLoadingSelectorClients.value = false;
    }
};

const loadPrestamos = async () => {
    if (!isPrestamosView.value) {
        return;
    }

    isLoadingPrestamos.value = true;

    try {
        const response = await axios.get('/admin/client-capture/prestamos', {
            params: {
                q: prestamoSearch.value.trim(),
            },
        });

        prestamos.value = response.data?.data ?? [];
    } catch (error) {
        console.error('Error al cargar préstamos:', error);
        alert('No se pudieron cargar los préstamos.');
    } finally {
        isLoadingPrestamos.value = false;
    }
};

const resolvePrestamo = async (
    numeroPrestamo: number,
    estatus: 'ACEPTADO' | 'RECHAZADO'
) => {
    if (isUpdatingPrestamoById.value[numeroPrestamo]) {
        return;
    }

    isUpdatingPrestamoById.value[numeroPrestamo] = true;

    try {
        await axios.patch(`/admin/client-capture/${numeroPrestamo}/prestamo-status`, {
            estatus_prestamo: estatus,
        });

        prestamos.value = prestamos.value.map((prestamo) =>
            prestamo.numero_prestamo === numeroPrestamo
                ? { ...prestamo, estatus_prestamo: estatus }
                : prestamo
        );
    } catch (error) {
        console.error('Error al actualizar estatus del préstamo:', error);
        alert('No se pudo actualizar el estatus del préstamo.');
    } finally {
        isUpdatingPrestamoById.value[numeroPrestamo] = false;
    }
};

const getPrestamoBadgeClass = (estatus: 'PENDIENTE' | 'ACEPTADO' | 'RECHAZADO') => {
    if (estatus === 'ACEPTADO') {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
    }

    if (estatus === 'RECHAZADO') {
        return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300';
    }

    return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
};

const openRiskClassification = (numeroCliente: number) => {
    goToSection('clasificacion-riesgo', { riskClient: String(numeroCliente) });
};

const backToRiskClientsList = () => {
    goToSection('clasificacion-riesgo');
};

const loadSystemUsers = async () => {
    if (!isSystemUsersView.value) {
        return;
    }

    isLoadingSystemUsers.value = true;

    try {
        const response = await axios.get('/admin/system-users', {
            params: {
                q: systemUserSearch.value.trim(),
            },
        });

        systemUsers.value = response.data?.data ?? [];
        systemRoles.value = response.data?.roles ?? [];
    } catch (error) {
        console.error('Error al cargar usuarios del sistema:', error);
        alert('No se pudieron cargar los usuarios del sistema.');
    } finally {
        isLoadingSystemUsers.value = false;
    }
};

const resetSystemUserForm = () => {
    systemUserForm.value = {
        name: '',
        email: '',
        status: 1,
        role: 'customer',
        password: '',
        password_confirmation: '',
    };
    editingSystemUserId.value = null;
    systemUserModalMode.value = 'create';
};

const openCreateSystemUserModal = () => {
    resetSystemUserForm();
    systemUserModalMode.value = 'create';
    isSystemUserModalOpen.value = true;
};

const openEditSystemUserModal = (user: { id: number; name: string; email: string; status: number; role: string | null }) => {
    editingSystemUserId.value = user.id;
    systemUserModalMode.value = 'edit';
    systemUserForm.value = {
        name: user.name,
        email: user.email,
        status: user.status ?? 1,
        role: user.role ?? 'customer',
        password: '',
        password_confirmation: '',
    };
    isSystemUserModalOpen.value = true;
};

const closeSystemUserModal = () => {
    isSystemUserModalOpen.value = false;
    resetSystemUserForm();
};

const saveSystemUser = async () => {
    if (isSavingSystemUser.value) {
        return;
    }

    isSavingSystemUser.value = true;

    try {
        if (systemUserModalMode.value === 'create') {
            await axios.post('/admin/system-users', {
                name: systemUserForm.value.name,
                email: systemUserForm.value.email,
                status: systemUserForm.value.status,
                role: systemUserForm.value.role,
                password: systemUserForm.value.password,
                password_confirmation: systemUserForm.value.password_confirmation,
            });
            alert('Usuario creado correctamente.');
        } else if (editingSystemUserId.value) {
            await axios.patch(`/admin/system-users/${editingSystemUserId.value}`, {
                name: systemUserForm.value.name,
                email: systemUserForm.value.email,
                status: systemUserForm.value.status,
                role: systemUserForm.value.role,
            });
            alert('Usuario actualizado correctamente.');
        }

        closeSystemUserModal();
        await loadSystemUsers();
    } catch (error) {
        console.error('Error al guardar usuario del sistema:', error);
        alert('No se pudo guardar el usuario. Verifica los datos.');
    } finally {
        isSavingSystemUser.value = false;
    }
};

const openSystemUserPasswordModal = (user: { id: number; name: string }) => {
    passwordTargetUser.value = { id: user.id, name: user.name };
    systemUserPasswordForm.value = {
        password: '',
        password_confirmation: '',
    };
    isSystemUserPasswordModalOpen.value = true;
};

const closeSystemUserPasswordModal = () => {
    isSystemUserPasswordModalOpen.value = false;
    passwordTargetUser.value = null;
    systemUserPasswordForm.value = {
        password: '',
        password_confirmation: '',
    };
};

const updateSystemUserPassword = async () => {
    if (!passwordTargetUser.value || isSavingSystemUserPassword.value) {
        return;
    }

    isSavingSystemUserPassword.value = true;

    try {
        await axios.patch(`/admin/system-users/${passwordTargetUser.value.id}/password`, {
            password: systemUserPasswordForm.value.password,
            password_confirmation: systemUserPasswordForm.value.password_confirmation,
        });
        alert('Contraseña actualizada correctamente.');
        closeSystemUserPasswordModal();
    } catch (error) {
        console.error('Error al actualizar contraseña:', error);
        alert('No se pudo actualizar la contraseña.');
    } finally {
        isSavingSystemUserPassword.value = false;
    }
};

const loadReportesAnonimos = async () => {
    if (!isAlertasAnonimasView.value) {
        return;
    }

    isLoadingReportesAnonimos.value = true;

    try {
        const response = await axios.get('/admin/alertas-anonimas');
        reportesAnonimos.value = response.data?.data ?? [];
    } catch (error) {
        console.error('Error al cargar reportes anónimos:', error);
        alert('No se pudieron cargar los reportes anónimos.');
    } finally {
        isLoadingReportesAnonimos.value = false;
    }
};

const setAlertasTab = async (tab: 'buzon' | 'reportes') => {
    alertasTab.value = tab;

    if (tab === 'reportes') {
        await loadReportesAnonimos();
    }
};

const submitAlertaAnonima = async () => {
    if (isSavingAlertaAnonima.value) {
        return;
    }

    if (!alertaAnonimaForm.value.denuncia.trim()) {
        alert('Escribe el contenido de la denuncia.');
        return;
    }

    isSavingAlertaAnonima.value = true;

    try {
        const response = await axios.post('/admin/alertas-anonimas', {
            destinatario: alertaAnonimaForm.value.destinatario.trim(),
            asunto: alertaAnonimaForm.value.asunto.trim(),
            empleado_reportado: alertaAnonimaForm.value.empleado_reportado.trim(),
            motivo: alertaAnonimaForm.value.motivo.trim(),
            detalle: alertaAnonimaForm.value.detalle.trim(),
            denuncia: alertaAnonimaForm.value.denuncia.trim(),
        });

        const reporteCreado = response.data?.data;
        if (reporteCreado) {
            reportesAnonimos.value.unshift(reporteCreado);
        } else {
            await loadReportesAnonimos();
        }

        alertaAnonimaForm.value = {
            destinatario: '',
            asunto: '',
            empleado_reportado: '',
            motivo: '',
            detalle: '',
            denuncia: '',
        };
        await setAlertasTab('reportes');
        alert('Reporte enviado correctamente.');
    } catch (error) {
        console.error('Error al guardar reporte anónimo:', error);
        alert('No se pudo guardar el reporte. Intenta de nuevo.');
    } finally {
        isSavingAlertaAnonima.value = false;
    }
};

const markReporteAsRead = async (reporteId: number) => {
    if (isMarkingReporteById.value[reporteId]) {
        return;
    }

    isMarkingReporteById.value[reporteId] = true;

    try {
        const response = await axios.patch(`/admin/alertas-anonimas/${reporteId}/leida`);
        const reporteActualizado = response.data?.data;

        if (reporteActualizado) {
            reportesAnonimos.value = reportesAnonimos.value.map((reporte) =>
                reporte.id === reporteId ? reporteActualizado : reporte
            );
        } else {
            await loadReportesAnonimos();
        }
    } catch (error) {
        console.error('Error al marcar reporte como leído:', error);
        alert('No se pudo marcar el reporte como leído.');
    } finally {
        isMarkingReporteById.value[reporteId] = false;
    }
};

const deleteReporteAnonimo = async (reporteId: number) => {
    const confirmed = window.confirm('¿Eliminar este reporte? Esta acción no se puede deshacer.');
    if (!confirmed || isDeletingReporteById.value[reporteId]) {
        return;
    }

    isDeletingReporteById.value[reporteId] = true;

    try {
        await axios.delete(`/admin/alertas-anonimas/${reporteId}`);
        reportesAnonimos.value = reportesAnonimos.value.filter((reporte) => reporte.id !== reporteId);
        alert('Reporte eliminado correctamente.');
    } catch (error) {
        console.error('Error al eliminar reporte:', error);
        alert('No se pudo eliminar el reporte.');
    } finally {
        isDeletingReporteById.value[reporteId] = false;
    }
};

const downloadAlertasXml = async () => {
    if (isDownloadingXml.value) {
        return;
    }

    if (!selectedXmlClientId.value) {
        alert('Selecciona un cliente para generar el XML.');
        return;
    }

    isDownloadingXml.value = true;

    try {
        const response = await axios.get('/admin/alertas/download-xml', {
            params: {
                client_id: selectedXmlClientId.value,
            },
            responseType: 'blob',
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'alertas_operaciones_relevantes_tabla.xml');

        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        alert('Archivo XML descargado correctamente.');
    } catch (error) {
        console.error('Error al descargar XML:', error);
        alert('No se pudo generar el archivo XML.');
    } finally {
        isDownloadingXml.value = false;
    }
};

const downloadAlertasXmlRules = async () => {
    if (isDownloadingXmlRules.value) {
        return;
    }

    if (!selectedXmlClientId.value) {
        alert('Selecciona un cliente para generar el XML.');
        return;
    }

    isDownloadingXmlRules.value = true;

    try {
        const response = await axios.get('/admin/alertas/download-xml-rules', {
            params: {
                client_id: selectedXmlClientId.value,
            },
            responseType: 'blob',
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'tabla_operaciones_relevantes_reglas.xml');

        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        alert('Archivo XML (reglas) descargado correctamente.');
    } catch (error) {
        console.error('Error al descargar XML (reglas):', error);
        alert('No se pudo generar el archivo XML (reglas).');
    } finally {
        isDownloadingXmlRules.value = false;
    }
};

const setAnexoInputRef = (element: unknown, numeroCliente: number) => {
    anexoInputRefs.value[numeroCliente] = element instanceof HTMLInputElement ? element : null;
};

const openAnexoPicker = (numeroCliente: number) => {
    if (isUploadingAnexoByClient.value[numeroCliente]) {
        return;
    }

    anexoInputRefs.value[numeroCliente]?.click();
};

const setAnexoFile = async (event: Event, numeroCliente: number) => {
    const target = event.target as HTMLInputElement;
    anexoFilesByClient.value[numeroCliente] = target.files?.[0] ?? null;

    if (anexoFilesByClient.value[numeroCliente]) {
        await uploadAnexo(numeroCliente);
    }
};

const uploadAnexo = async (numeroCliente: number) => {
    const selectedFile = anexoFilesByClient.value[numeroCliente];

    if (!selectedFile) {
        alert('Selecciona un archivo antes de subir.');
        return;
    }

    isUploadingAnexoByClient.value[numeroCliente] = true;

    try {
        const payload = new FormData();
        payload.append('anexo', selectedFile);

        await axios.post(`/admin/client-capture/${numeroCliente}/anexo`, payload, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        alert('Anexo subido correctamente.');
        anexoFilesByClient.value[numeroCliente] = null;
        const input = anexoInputRefs.value[numeroCliente];
        if (input) {
            input.value = '';
        }
        await loadSelectorClients();
    } catch (error) {
        console.error('Error al subir anexo:', error);
        alert('No se pudo subir el anexo.');
    } finally {
        isUploadingAnexoByClient.value[numeroCliente] = false;
    }
};

const formatFileSize = (sizeBytes: number | null) => {
    if (!sizeBytes || sizeBytes <= 0) {
        return '';
    }

    if (sizeBytes < 1024) {
        return `${sizeBytes} B`;
    }

    if (sizeBytes < 1024 * 1024) {
        return `${(sizeBytes / 1024).toFixed(1)} KB`;
    }

    return `${(sizeBytes / (1024 * 1024)).toFixed(1)} MB`;
};

const openClientAnexos = async (numeroCliente: number, cliente: string) => {
    isAnexoModalOpen.value = true;
    isLoadingClientAnexos.value = true;
    selectedClientForAnexos.value = { numero_cliente: numeroCliente, cliente };
    selectedClientAnexos.value = [];

    try {
        const response = await axios.get(`/admin/client-capture/${numeroCliente}/anexos`);
        selectedClientAnexos.value = response.data?.data ?? [];
    } catch (error) {
        console.error('Error al cargar anexos del cliente:', error);
        alert('No se pudo obtener la lista de anexos.');
    } finally {
        isLoadingClientAnexos.value = false;
    }
};

const closeClientAnexos = () => {
    isAnexoModalOpen.value = false;
    selectedClientForAnexos.value = null;
    selectedClientAnexos.value = [];
};

const downloadAnexoByUrl = (downloadUrl: string) => {
    window.open(downloadUrl, '_blank');
};

const editClient = (numeroCliente: number) => {
    goToSection('captura-cliente', { editClient: String(numeroCliente) });
};

const deleteClient = async (numeroCliente: number) => {
    const confirmed = window.confirm('¿Eliminar este cliente? Esta acción no se puede deshacer.');
    if (!confirmed) {
        return;
    }

    isDeletingClientById.value[numeroCliente] = true;

    try {
        await axios.delete(`/admin/client-capture/${numeroCliente}`);
        alert('Cliente eliminado correctamente.');
        await loadSelectorClients();
    } catch (error) {
        console.error('Error al eliminar cliente:', error);
        alert('No se pudo eliminar el cliente.');
    } finally {
        isDeletingClientById.value[numeroCliente] = false;
    }
};

const setClientValidation = (numeroCliente: number, value: string) => {
    if (value !== '' && value !== 'SI' && value !== 'NO') {
        return;
    }

    selectedValidationByClient.value[numeroCliente] = value as '' | 'SI' | 'NO';
    dirtyValidationByClient.value[numeroCliente] = true;
};

const saveClientValidations = async () => {
    if (isSavingValidations.value) {
        return;
    }

    const pendingClients = selectorClients.value.filter((client) => {
        const numeroCliente = client.numero_cliente;
        const selectedValue = selectedValidationByClient.value[numeroCliente];
        return dirtyValidationByClient.value[numeroCliente] && (selectedValue === 'SI' || selectedValue === 'NO');
    });

    if (!pendingClients.length) {
        alert('No hay validaciones pendientes por guardar.');
        return;
    }

    isSavingValidations.value = true;

    try {
        const results = await Promise.allSettled(
            pendingClients.map((client) =>
                axios.patch(`/admin/client-capture/${client.numero_cliente}/validacion`, {
                    cliente_validado: selectedValidationByClient.value[client.numero_cliente],
                })
            )
        );

        const failedUpdates = results.filter((result) => result.status === 'rejected');

        if (failedUpdates.length) {
            alert(`Se guardaron ${pendingClients.length - failedUpdates.length} validaciones, pero ${failedUpdates.length} fallaron.`);
        } else {
            alert('Validaciones guardadas correctamente.');
        }

        await loadSelectorClients();
    } catch (error) {
        console.error('Error al guardar validaciones:', error);
        alert('No se pudieron guardar las validaciones.');
    } finally {
        isSavingValidations.value = false;
    }
};

// Search function
const handleSearch = () => {
    if (searchQuery.value.trim()) {
        const value = searchQuery.value;
        axios.get(admin.excel.search.data.url(), {
            params: {
                value: value,
                current: current.value,
                pageSize: pageSize.value
            }
        }).then(response => {
            const responseData = response.data.data;
            // El backend ahora devuelve { results: {...paginación}, alertas_generadas: N }
            const pagination = responseData['results'] ?? responseData;
            data.value = pagination['data'];
            total.value = pagination['total'];
            current.value = pagination['current_page'];
            pageSize.value = pagination['per_page'];

            // Si se encontraron coincidencias en lista negra, mostrar aviso y recargar alertas
            // coincidencias = total hallado en lista_negra (muestra badge SIEMPRE que haya match)
            // alertas_generadas = solo las nuevas (para saber si hay que recargar la lista)
            const coincidencias = responseData['coincidencias'] ?? 0;
            const alertasGeneradas = responseData['alertas_generadas'] ?? 0;
            if (coincidencias > 0) {
                // Cancelar timeout previo para evitar que oculte este nuevo badge
                if (searchListaNegraAlertoTimeout !== null) {
                    clearTimeout(searchListaNegraAlertoTimeout);
                    searchListaNegraAlertoTimeout = null;
                }
                searchListaNegraAlerta.value = { count: coincidencias, visible: true };
                loadAlertasListaNegra();
                searchListaNegraAlertoTimeout = setTimeout(() => {
                    searchListaNegraAlerta.value = { count: 0, visible: false };
                    searchListaNegraAlertoTimeout = null;
                }, 12000);
            } else {
                // Sin coincidencias: ocultar badge si estaba visible
                if (searchListaNegraAlertoTimeout !== null) {
                    clearTimeout(searchListaNegraAlertoTimeout);
                    searchListaNegraAlertoTimeout = null;
                }
                searchListaNegraAlerta.value = { count: 0, visible: false };
                if (alertasGeneradas > 0) {
                    loadAlertasListaNegra();
                }
            }
        }).catch(error => {
            console.log(error);
        });
    }
};

const onShowSizeChange = (newCurrent: number, newPageSize: number) => {
    pageSize.value = newPageSize;
    current.value = newCurrent;
    handleSearch();
}

const onChangePage = (newCurrent: number) => {
    current.value = newCurrent;
    handleSearch();
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

watch(currentSection, async (section) => {
    selectedRiskClientId.value = readRiskClientIdFromUrl();

    if (section === 'captura-cliente') {
        resetCaptureForm();
        const editClientId = readEditClientIdFromUrl();
        if (editClientId) {
            await loadClientForEdit(editClientId);
        }
    }

    if (section === 'seleccionar-clientes' || section === 'clasificacion-riesgo') {
        await loadSelectorClients();
    }

    if (section === 'prestamos') {
        await loadPrestamos();
    }

    if (section === 'alertas') {
        alertasVerticalTab.value = 'operaciones-24h';
        await loadSelectorClients();
    }

    if (section === 'alertas-anonimas') {
        alertasTab.value = 'buzon';
        await loadReportesAnonimos();
    }

    if (section === 'usuarios-sistema') {
        await loadSystemUsers();
    }
});

onMounted(async () => {
    selectedRiskClientId.value = readRiskClientIdFromUrl();

    if (isCapturaClienteView.value) {
        resetCaptureForm();
        const editClientId = readEditClientIdFromUrl();
        if (editClientId) {
            await loadClientForEdit(editClientId);
        }
    }

    if (isSeleccionarClientesView.value || isClasificacionRiesgoView.value) {
        await loadSelectorClients();
    }

    if (isPrestamosView.value) {
        await loadPrestamos();
    }

    if (isAlertasView.value) {
        alertasVerticalTab.value = 'operaciones-24h';
        await loadSelectorClients();
    }

    if (isAlertasAnonimasView.value) {
        alertasTab.value = 'buzon';
        await loadReportesAnonimos();
    }

    if (isSystemUsersView.value) {
        await loadSystemUsers();
    }
});

</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <template v-if="isCapturaClienteView">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <h2 class="mb-4 text-xl font-semibold text-slate-900 dark:text-slate-100">Captura de cliente</h2>
                    <div
                        v-if="editingClientId"
                        class="mb-4 flex items-center justify-between rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200"
                    >
                        <span>Editando cliente #{{ editingClientId }}</span>
                        <button
                            type="button"
                            class="rounded bg-slate-700 px-3 py-1 text-xs text-white hover:bg-slate-800"
                            @click="goToSection('seleccionar-clientes')"
                        >
                            Cancelar edición
                        </button>
                    </div>
                    <div
                        v-if="isLoadingCaptureForEdit"
                        class="mb-4 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                    >
                        Cargando información del cliente...
                    </div>

                    <div class="mb-4">
                        <label for="capture-option" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Tipo de solicitud
                        </label>
                        <select
                            id="capture-option"
                            v-model="selectedCaptureOption"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        >
                            <option v-for="option in captureOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Pestañas -->
                    <div class="mb-4 flex flex-wrap gap-2 border-b border-slate-200 pb-3 dark:border-slate-800">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            type="button"
                            @click="setActiveTab(tab.id)"
                            :class="[
                                'rounded-md px-3 py-1 text-xs font-medium transition-colors',
                                activeTab === tab.id
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                            ]"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <!-- Contenido de las pestañas -->
                    <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                        <component
                            :is="activeTab === 'medio-contacto' ? MedioContacto : 
                                 activeTab === 'datos-identificacion' ? DatosIdentificacion :
                                 activeTab === 'datos-laborales' ? DatosLaborales :
                                 activeTab === 'solicitud-operacion' ? SolicitudOperacion :
                                 activeTab === 'datos-contacto' ? DatosContacto :
                                 activeTab === 'garantias' ? Garantias :
                                 PLD"
                            v-model="formData[
                                activeTab === 'medio-contacto' ? 'medioContacto' :
                                activeTab === 'datos-identificacion' ? 'datosIdentificacion' :
                                activeTab === 'datos-laborales' ? 'datosLaborales' :
                                activeTab === 'solicitud-operacion' ? 'solicitudOperacion' :
                                activeTab === 'datos-contacto' ? 'datosContacto' :
                                activeTab === 'garantias' ? 'garantias' :
                                'pld'
                            ]"
                        />

                        <div class="mt-6 flex justify-end gap-3">
                            <button
                                type="button"
                                @click="saveForm"
                                :disabled="isSaving || isLoadingCaptureForEdit"
                                class="rounded-md bg-blue-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                            >
                                {{ isSaving ? 'Guardando...' : (editingClientId ? 'Guardar cambios' : 'Guardar información') }}
                            </button>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else-if="isSeleccionarClientesView">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <h2 class="mb-4 text-xl font-semibold text-slate-900 dark:text-slate-100">Seleccionar clientes</h2>

                    <div class="mb-4">
                        <input
                            v-model="selectorSearch"
                            type="text"
                            placeholder="Buscar por cliente o RFC..."
                            @input="loadSelectorClients"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        />
                    </div>
                    <div class="mb-4 flex justify-end">
                        <button
                            type="button"
                            class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="isSavingValidations"
                            @click="saveClientValidations"
                        >
                            {{ isSavingValidations ? 'Guardando validaciones...' : 'Guardar validaciones' }}
                        </button>
                    </div>

                    <a-table
                        :dataSource="selectorClients"
                        :loading="isLoadingSelectorClients"
                        :pagination="false"
                        :locale="{ emptyText: 'Sin clientes registrados' }"
                        rowKey="numero_cliente"
                        bordered
                    >
                        <a-table-column key="numero_cliente" title="Numero de cliente" data-index="numero_cliente" :width="110" />
                        <a-table-column key="cliente" title="Cliente" data-index="cliente" />
                        <a-table-column key="rfc" title="RFC" data-index="rfc" />
                        <a-table-column key="tipo_persona" title="Tipo de persona" data-index="tipo_persona" />
                        <a-table-column key="subir_anexo" title="Subir anexo" :width="180">
                            <template #default="{ record }">
                                <div class="flex items-center gap-2">
                                    <input
                                        type="file"
                                        class="hidden"
                                        :ref="(element) => setAnexoInputRef(element, record.numero_cliente)"
                                        @change="setAnexoFile($event, record.numero_cliente)"
                                    />
                                    <button
                                        type="button"
                                        class="rounded bg-blue-600 px-3 py-1 text-xs text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="isUploadingAnexoByClient[record.numero_cliente]"
                                        @click="openAnexoPicker(record.numero_cliente)"
                                    >
                                        {{ isUploadingAnexoByClient[record.numero_cliente] ? 'Subiendo...' : 'Subir anexo' }}
                                    </button>
                                </div>
                            </template>
                        </a-table-column>
                        <a-table-column key="ver_anexo" title="Archivos del cliente" :width="260">
                            <template #default="{ record }">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="rounded bg-slate-700 px-3 py-1 text-xs text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="!record.anexo_disponible"
                                        @click="openClientAnexos(record.numero_cliente, record.cliente)"
                                    >
                                        Ver archivos
                                    </button>
                                    <span class="max-w-[130px] truncate text-xs text-slate-600 dark:text-slate-300">
                                        {{ record.anexo_disponible ? `${record.anexo_total} archivo(s)` : 'Sin archivo' }}
                                    </span>
                                </div>
                            </template>
                        </a-table-column>
                        <a-table-column key="validar_cliente" title="Validar cliente" :width="170">
                            <template #default="{ record }">
                                <select
                                    :value="selectedValidationByClient[record.numero_cliente] ?? record.cliente_validado ?? ''"
                                    class="w-full rounded border border-slate-300 bg-white px-2 py-1 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    @change="setClientValidation(record.numero_cliente, ($event.target as HTMLSelectElement).value)"
                                >
                                    <option value="">Seleccionar...</option>
                                    <option value="SI">SI (Aceptar)</option>
                                    <option value="NO">NO (Rechazar)</option>
                                </select>
                            </template>
                        </a-table-column>
                        <a-table-column key="acciones_cliente" title="Acciones" :width="130">
                            <template #default="{ record }">
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        class="rounded bg-amber-500 px-2 py-1 text-[11px] text-white hover:bg-amber-600"
                                        @click="editClient(record.numero_cliente)"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded bg-red-600 px-2 py-1 text-[11px] text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="isDeletingClientById[record.numero_cliente]"
                                        @click="deleteClient(record.numero_cliente)"
                                    >
                                        {{ isDeletingClientById[record.numero_cliente] ? '...' : 'X' }}
                                    </button>
                                </div>
                            </template>
                        </a-table-column>
                    </a-table>
                </div>

                <div
                    v-if="isAnexoModalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                >
                    <div class="max-h-[80vh] w-full max-w-2xl overflow-hidden rounded-lg bg-white shadow-xl dark:bg-slate-900">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">
                                Archivos de {{ selectedClientForAnexos?.cliente || 'cliente' }}
                            </h3>
                            <button
                                type="button"
                                class="rounded bg-slate-200 px-2 py-1 text-xs text-slate-800 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600"
                                @click="closeClientAnexos"
                            >
                                Cerrar
                            </button>
                        </div>

                        <div class="max-h-[60vh] overflow-y-auto p-4">
                            <p
                                v-if="isLoadingClientAnexos"
                                class="text-sm text-slate-600 dark:text-slate-300"
                            >
                                Cargando anexos...
                            </p>

                            <p
                                v-else-if="!selectedClientAnexos.length"
                                class="text-sm text-slate-600 dark:text-slate-300"
                            >
                                Este cliente no tiene anexos.
                            </p>

                            <div
                                v-else
                                class="space-y-2"
                            >
                                <div
                                    v-for="anexo in selectedClientAnexos"
                                    :key="anexo.id ?? anexo.download_url"
                                    class="flex items-center justify-between rounded border border-slate-200 p-3 dark:border-slate-700"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">
                                            {{ anexo.nombre }}
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ anexo.fecha_carga || 'Fecha no disponible' }}{{ formatFileSize(anexo.size_bytes) ? ` · ${formatFileSize(anexo.size_bytes)}` : '' }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        class="rounded bg-blue-600 px-3 py-1 text-xs text-white hover:bg-blue-700"
                                        @click="downloadAnexoByUrl(anexo.download_url)"
                                    >
                                        Descargar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else-if="isPrestamosView">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <h2 class="mb-4 text-xl font-semibold text-slate-900 dark:text-slate-100">Prestamo</h2>

                    <div class="mb-4">
                        <input
                            v-model="prestamoSearch"
                            type="text"
                            placeholder="Buscar por número de préstamo o cliente..."
                            @input="loadPrestamos"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        />
                    </div>

                    <a-table
                        :dataSource="prestamos"
                        :loading="isLoadingPrestamos"
                        :pagination="false"
                        :locale="{ emptyText: 'Sin préstamos registrados' }"
                        rowKey="numero_prestamo"
                        bordered
                    >
                        <a-table-column
                            key="numero_prestamo"
                            title="Numero de prestamo"
                            data-index="numero_prestamo"
                            :width="170"
                        />
                        <a-table-column
                            key="nombre_cliente"
                            title="Nombre del cliente"
                            data-index="nombre_cliente"
                        />
                        <a-table-column key="monto_solicitado" title="Monto solicitado" :width="180">
                            <template #default="{ record }">
                                <div class="flex flex-col gap-1">
                                    <span
                                        class="w-fit rounded px-2 py-1 text-xs font-semibold"
                                        :class="[
                                            getMontoRiesgoMeta(record.tipo_solicitud, record.monto_solicitado).textClass,
                                            getMontoRiesgoMeta(record.tipo_solicitud, record.monto_solicitado).bgClass,
                                        ]"
                                    >
                                        {{ getMontoRiesgoMeta(record.tipo_solicitud, record.monto_solicitado).label }}
                                    </span>
                                    <span class="font-medium">
                                        $ {{ numeralFormat(parseMontoSolicitado(record.monto_solicitado), '0,0.00') }}
                                    </span>
                                </div>
                            </template>
                        </a-table-column>
                        <a-table-column key="estatus_prestamo" title="Estatus" :width="140">
                            <template #default="{ record }">
                                <span
                                    class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                    :class="getPrestamoBadgeClass(record.estatus_prestamo)"
                                >
                                    {{ record.estatus_prestamo }}
                                </span>
                            </template>
                        </a-table-column>
                        <a-table-column key="acciones_prestamo" title="Acciones" :width="220">
                            <template #default="{ record }">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="rounded bg-emerald-600 px-3 py-1 text-xs text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="isUpdatingPrestamoById[record.numero_prestamo]"
                                        @click="resolvePrestamo(record.numero_prestamo, 'ACEPTADO')"
                                    >
                                        Aceptar
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded bg-red-600 px-3 py-1 text-xs text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="isUpdatingPrestamoById[record.numero_prestamo]"
                                        @click="resolvePrestamo(record.numero_prestamo, 'RECHAZADO')"
                                    >
                                        Rechazar
                                    </button>
                                </div>
                            </template>
                        </a-table-column>
                    </a-table>
                </div>
            </template>
            <template v-else-if="isUmbralView">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <h2 class="mb-4 text-xl font-semibold text-slate-900 dark:text-slate-100">Umbral</h2>

                    <div class="mb-4 flex gap-2 border-b border-slate-200 pb-3 dark:border-slate-800">
                        <button
                            v-for="tab in umbralTabs"
                            :key="tab.id"
                            type="button"
                            class="rounded-md px-3 py-1 text-xs font-medium transition-colors"
                            :class="activeUmbralTab === tab.id
                                ? 'bg-blue-600 text-white'
                                : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'"
                            @click="activeUmbralTab = tab.id"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <div v-if="activeUmbralTab === 'saldo'" class="space-y-4">
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                            <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-200">
                                Persona fisica (efectivo)
                            </h3>

                            <div class="space-y-3">
                                <div
                                    v-for="item in umbralPersonaFisica"
                                    :key="`fisica-${item.rango}`"
                                    class="grid grid-cols-[160px_1fr_150px] items-center gap-3"
                                >
                                    <span class="text-xs font-medium text-slate-700 dark:text-slate-200">
                                        {{ item.rango }}
                                    </span>

                                    <div class="h-4 w-full overflow-hidden rounded bg-slate-200 dark:bg-slate-700">
                                        <div
                                            class="h-full rounded transition-all"
                                            :class="item.color"
                                            :style="{ width: getRangeWidth(item.from, item.to, 600000) }"
                                        />
                                    </div>

                                    <span
                                        class="inline-flex w-fit rounded-full px-2 py-1 text-xs font-semibold"
                                        :class="item.badge"
                                    >
                                        {{ item.riesgo }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                            <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-200">
                                Personas morales y fideicomisos (efectivo)
                            </h3>

                            <div class="space-y-3">
                                <div
                                    v-for="item in umbralPersonaMoralFideicomiso"
                                    :key="`moral-${item.rango}`"
                                    class="grid grid-cols-[160px_1fr_150px] items-center gap-3"
                                >
                                    <span class="text-xs font-medium text-slate-700 dark:text-slate-200">
                                        {{ item.rango }}
                                    </span>

                                    <div class="h-4 w-full overflow-hidden rounded bg-slate-200 dark:bg-slate-700">
                                        <div
                                            class="h-full rounded transition-all"
                                            :class="item.color"
                                            :style="{ width: getRangeWidth(item.from, item.to, 600000) }"
                                        />
                                    </div>

                                    <span
                                        class="inline-flex w-fit rounded-full px-2 py-1 text-xs font-semibold"
                                        :class="item.badge"
                                    >
                                        {{ item.riesgo }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="space-y-4">
                        <div class="rounded-md border border-rose-200 bg-rose-50 p-4 dark:border-rose-800/50 dark:bg-rose-900/20">
                            <h3 class="mb-2 text-sm font-semibold text-rose-800 dark:text-rose-300">
                                ALTO RIESGO (expresamente listadas en Anexo 5)
                            </h3>
                            <p class="mb-2 text-xs text-slate-600 dark:text-slate-300">
                                Final Manual de Cumplimiento AR…
                            </p>
                            <ul class="list-disc space-y-1 pl-5 text-sm text-slate-700 dark:text-slate-200">
                                <li>Baja California (en el Anexo aparece como “Baja California Norte”)</li>
                                <li>Chihuahua</li>
                                <li>Guerrero</li>
                                <li>Jalisco</li>
                                <li>Michoacán</li>
                                <li>Sinaloa</li>
                                <li>Tamaulipas</li>
                            </ul>
                        </div>

                        <div class="rounded-md border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/50 dark:bg-amber-900/20">
                            <h3 class="mb-2 text-sm font-semibold text-amber-800 dark:text-amber-300">
                                RIESGO MEDIO (por criterio del manual: “zonas fronterizas”)
                            </h3>
                            <p class="mb-2 text-xs text-slate-600 dark:text-slate-300">
                                (Estados con frontera internacional no incluidos en el Anexo 5; se dejan en “medio” por el componente “zonas fronterizas” del manual)
                                · Final Manual de Cumplimiento AR…
                            </p>
                            <ul class="list-disc space-y-1 pl-5 text-sm text-slate-700 dark:text-slate-200">
                                <li>Sonora</li>
                                <li>Coahuila</li>
                                <li>Nuevo León</li>
                                <li>Campeche</li>
                                <li>Chiapas</li>
                                <li>Tabasco</li>
                                <li>Quintana Roo</li>
                            </ul>
                        </div>

                        <div class="rounded-md border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800/50 dark:bg-emerald-900/20">
                            <h3 class="mb-2 text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                                RIESGO BAJO (resto de Entidades Federativas)
                            </h3>
                            <p class="text-sm text-slate-700 dark:text-slate-200">
                                Aguascalientes; Baja California Sur; Colima; Ciudad de México; Durango; Estado de México; Guanajuato; Hidalgo;
                                Morelos; Nayarit; Oaxaca; Puebla; Querétaro; San Luis Potosí; Tlaxcala; Veracruz; Yucatán; Zacatecas.
                            </p>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else-if="isClasificacionRiesgoView">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Clasificacion de riesgo</h2>
                        <button
                            v-if="selectedRiskClientId"
                            type="button"
                            class="rounded-md bg-slate-700 px-3 py-2 text-xs font-medium text-white hover:bg-slate-800"
                            @click="backToRiskClientsList"
                        >
                            Volver a clientes
                        </button>
                    </div>

                    <template v-if="!selectedRiskClientId">
                        <div class="mb-4">
                            <input
                                v-model="selectorSearch"
                                type="text"
                                placeholder="Buscar por cliente o RFC..."
                                @input="loadSelectorClients"
                                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            />
                        </div>

                        <a-table
                            :dataSource="selectorClients"
                            :loading="isLoadingSelectorClients"
                            :pagination="false"
                            :locale="{ emptyText: 'Sin clientes registrados' }"
                            rowKey="numero_cliente"
                            bordered
                        >
                            <a-table-column key="numero_cliente" title="Numero de cliente" data-index="numero_cliente" :width="120" />
                            <a-table-column key="cliente" title="Cliente" data-index="cliente" />
                            <a-table-column key="rfc" title="RFC" data-index="rfc" :width="170" />
                            <a-table-column key="tipo_persona" title="Tipo de persona" data-index="tipo_persona" :width="160" />
                            <a-table-column key="accion_riesgo" title="Clasificacion de riesgo" :width="190">
                                <template #default="{ record }">
                                    <button
                                        type="button"
                                        class="rounded bg-blue-600 px-3 py-1 text-xs text-white hover:bg-blue-700"
                                        @click="openRiskClassification(record.numero_cliente)"
                                    >
                                        Clasificacion de riesgo
                                    </button>
                                </template>
                            </a-table-column>
                        </a-table>
                    </template>

                    <template v-else>
                        <div class="mb-4 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                            Cliente:
                            <span class="font-semibold">
                                {{ selectedRiskClient?.cliente || `#${selectedRiskClientId}` }}
                            </span>
                        </div>

                        <div class="mb-4 flex gap-2 border-b border-slate-200 pb-3 dark:border-slate-800">
                            <button
                                v-for="tab in riskTabs"
                                :key="tab.id"
                                type="button"
                                class="rounded-md px-3 py-1 text-xs font-medium transition-colors"
                                :class="activeRiskTab === tab.id
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'"
                                @click="activeRiskTab = tab.id"
                            >
                                {{ tab.label }}
                            </button>
                        </div>

                        <div v-if="activeRiskTab === 'indicadores-generales'" class="space-y-4">
                            <div class="rounded-md border border-emerald-200 bg-emerald-50 py-2 text-center text-sm font-semibold text-emerald-800">
                                Indicadores Generales
                            </div>

                            <div class="grid gap-4 lg:grid-cols-[1.6fr_1fr]">
                                <div class="rounded-md border border-rose-200 bg-rose-50 p-3">
                                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-rose-700">Matriz general</h3>
                                    <table class="w-full border border-slate-300 text-xs">
                                        <thead>
                                            <tr class="bg-slate-100 text-slate-700">
                                                <th class="border border-slate-300 px-2 py-1 text-left">Categoria</th>
                                                <th class="border border-slate-300 px-2 py-1">Alto</th>
                                                <th class="border border-slate-300 px-2 py-1">Medio</th>
                                                <th class="border border-slate-300 px-2 py-1">Bajo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="border border-slate-300 px-2 py-1">Producto</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">55.00</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">41.00</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">30.00</td>
                                            </tr>
                                            <tr>
                                                <td class="border border-slate-300 px-2 py-1">Cliente Persona Fisica</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">325.00</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">98.00</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">45.00</td>
                                            </tr>
                                            <tr>
                                                <td class="border border-slate-300 px-2 py-1">Cliente Persona Moral</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">325.00</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">101.00</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">45.00</td>
                                            </tr>
                                            <tr>
                                                <td class="border border-slate-300 px-2 py-1">Zona geografica</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">3.00</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">3.00</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">3.00</td>
                                            </tr>
                                            <tr>
                                                <td class="border border-slate-300 px-2 py-1">Transacciones</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">150.00</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">80.00</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">37.50</td>
                                            </tr>
                                            <tr class="bg-slate-100 font-semibold">
                                                <td class="border border-slate-300 px-2 py-1">Total</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">858.00</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">323.00</td>
                                                <td class="border border-slate-300 px-2 py-1 text-center">160.50</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="space-y-4">
                                    <div class="rounded-md border border-amber-200 bg-amber-50 p-3">
                                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-700">Riesgo persona fisica</h3>
                                        <table class="w-full border border-slate-300 text-xs">
                                            <thead>
                                                <tr class="bg-slate-100 text-slate-700">
                                                    <th class="border border-slate-300 px-2 py-1 text-left">Nivel de riesgo</th>
                                                    <th class="border border-slate-300 px-2 py-1">De</th>
                                                    <th class="border border-slate-300 px-2 py-1">A</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="border border-slate-300 px-2 py-1">Riesgo Bajo</td>
                                                    <td class="border border-slate-300 px-2 py-1 text-center">0</td>
                                                    <td class="border border-slate-300 px-2 py-1 text-center">182</td>
                                                </tr>
                                                <tr>
                                                    <td class="border border-slate-300 px-2 py-1">Riesgo Alto</td>
                                                    <td class="border border-slate-300 px-2 py-1 text-center">183</td>
                                                    <td class="border border-slate-300 px-2 py-1 text-center">372</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="rounded-md border border-amber-200 bg-amber-50 p-3">
                                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-700">Riesgo persona moral</h3>
                                        <table class="w-full border border-slate-300 text-xs">
                                            <thead>
                                                <tr class="bg-slate-100 text-slate-700">
                                                    <th class="border border-slate-300 px-2 py-1 text-left">Nivel de riesgo</th>
                                                    <th class="border border-slate-300 px-2 py-1">De</th>
                                                    <th class="border border-slate-300 px-2 py-1">A</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="border border-slate-300 px-2 py-1">Riesgo Bajo</td>
                                                    <td class="border border-slate-300 px-2 py-1 text-center">0</td>
                                                    <td class="border border-slate-300 px-2 py-1 text-center">83</td>
                                                </tr>
                                                <tr>
                                                    <td class="border border-slate-300 px-2 py-1">Riesgo Medio</td>
                                                    <td class="border border-slate-300 px-2 py-1 text-center">84</td>
                                                    <td class="border border-slate-300 px-2 py-1 text-center">192</td>
                                                </tr>
                                                <tr>
                                                    <td class="border border-slate-300 px-2 py-1">Riesgo Alto</td>
                                                    <td class="border border-slate-300 px-2 py-1 text-center">193</td>
                                                    <td class="border border-slate-300 px-2 py-1 text-center">300</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="space-y-4">
                            <div class="rounded-md border border-amber-200 bg-amber-50 py-2 text-center text-sm font-semibold text-amber-800">
                                Detalle de categorias
                            </div>

                            <div class="grid gap-4 lg:grid-cols-2">
                                <div class="rounded-md border border-emerald-200 bg-emerald-50 p-3">
                                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-emerald-700">Matriz de indicador "Clientes persona fisica"</h3>
                                    <table class="w-full border border-slate-300 text-xs">
                                        <thead><tr class="bg-slate-100"><th class="border border-slate-300 px-2 py-1 text-left">Categoria</th><th class="border border-slate-300 px-2 py-1">Alto</th><th class="border border-slate-300 px-2 py-1">Medio</th><th class="border border-slate-300 px-2 py-1">Bajo</th></tr></thead>
                                        <tbody>
                                            <tr><td class="border border-slate-300 px-2 py-1">Actividad Economica</td><td class="border border-slate-300 px-2 py-1 text-center">35.00</td><td class="border border-slate-300 px-2 py-1 text-center">14.00</td><td class="border border-slate-300 px-2 py-1 text-center">7.00</td></tr>
                                            <tr><td class="border border-slate-300 px-2 py-1">Pais</td><td class="border border-slate-300 px-2 py-1 text-center">100.00</td><td class="border border-slate-300 px-2 py-1 text-center">7.00</td><td class="border border-slate-300 px-2 py-1 text-center">6.00</td></tr>
                                            <tr><td class="border border-slate-300 px-2 py-1">Nacionalidad</td><td class="border border-slate-300 px-2 py-1 text-center">85.00</td><td class="border border-slate-300 px-2 py-1 text-center">16.00</td><td class="border border-slate-300 px-2 py-1 text-center">8.00</td></tr>
                                            <tr><td class="border border-slate-300 px-2 py-1">Edad</td><td class="border border-slate-300 px-2 py-1 text-center">35.00</td><td class="border border-slate-300 px-2 py-1 text-center">20.00</td><td class="border border-slate-300 px-2 py-1 text-center">8.00</td></tr>
                                            <tr class="bg-slate-100 font-semibold"><td class="border border-slate-300 px-2 py-1">Total</td><td class="border border-slate-300 px-2 py-1 text-center">325.00</td><td class="border border-slate-300 px-2 py-1 text-center">98.00</td><td class="border border-slate-300 px-2 py-1 text-center">45.00</td></tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="rounded-md border border-sky-200 bg-sky-50 p-3">
                                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-sky-700">Matriz de indicador "Productos"</h3>
                                    <table class="w-full border border-slate-300 text-xs">
                                        <thead><tr class="bg-slate-100"><th class="border border-slate-300 px-2 py-1 text-left">Categoria</th><th class="border border-slate-300 px-2 py-1">Alto</th><th class="border border-slate-300 px-2 py-1">Medio</th><th class="border border-slate-300 px-2 py-1">Bajo</th></tr></thead>
                                        <tbody>
                                            <tr><td class="border border-slate-300 px-2 py-1">Destino de los recursos</td><td class="border border-slate-300 px-2 py-1 text-center">20.00</td><td class="border border-slate-300 px-2 py-1 text-center">10.00</td><td class="border border-slate-300 px-2 py-1 text-center">5.00</td></tr>
                                            <tr><td class="border border-slate-300 px-2 py-1">Origen de los recursos</td><td class="border border-slate-300 px-2 py-1 text-center">5.00</td><td class="border border-slate-300 px-2 py-1 text-center">10.00</td><td class="border border-slate-300 px-2 py-1 text-center">10.00</td></tr>
                                            <tr><td class="border border-slate-300 px-2 py-1">Propietario real</td><td class="border border-slate-300 px-2 py-1 text-center">20.00</td><td class="border border-slate-300 px-2 py-1 text-center">11.00</td><td class="border border-slate-300 px-2 py-1 text-center">5.00</td></tr>
                                            <tr><td class="border border-slate-300 px-2 py-1">Creditos</td><td class="border border-slate-300 px-2 py-1 text-center">10.00</td><td class="border border-slate-300 px-2 py-1 text-center">10.00</td><td class="border border-slate-300 px-2 py-1 text-center">10.00</td></tr>
                                            <tr class="bg-slate-100 font-semibold"><td class="border border-slate-300 px-2 py-1">Total</td><td class="border border-slate-300 px-2 py-1 text-center">55.00</td><td class="border border-slate-300 px-2 py-1 text-center">41.00</td><td class="border border-slate-300 px-2 py-1 text-center">30.00</td></tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="rounded-md border border-rose-200 bg-rose-50 p-3">
                                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-rose-700">Matriz de indicador "Clientes persona moral"</h3>
                                    <table class="w-full border border-slate-300 text-xs">
                                        <thead><tr class="bg-slate-100"><th class="border border-slate-300 px-2 py-1 text-left">Categoria</th><th class="border border-slate-300 px-2 py-1">Alto</th><th class="border border-slate-300 px-2 py-1">Medio</th><th class="border border-slate-300 px-2 py-1">Bajo</th></tr></thead>
                                        <tbody>
                                            <tr><td class="border border-slate-300 px-2 py-1">Actividad Economica</td><td class="border border-slate-300 px-2 py-1 text-center">35.00</td><td class="border border-slate-300 px-2 py-1 text-center">14.00</td><td class="border border-slate-300 px-2 py-1 text-center">7.00</td></tr>
                                            <tr><td class="border border-slate-300 px-2 py-1">Pais</td><td class="border border-slate-300 px-2 py-1 text-center">100.00</td><td class="border border-slate-300 px-2 py-1 text-center">7.00</td><td class="border border-slate-300 px-2 py-1 text-center">6.00</td></tr>
                                            <tr><td class="border border-slate-300 px-2 py-1">Nacionalidad</td><td class="border border-slate-300 px-2 py-1 text-center">85.00</td><td class="border border-slate-300 px-2 py-1 text-center">16.00</td><td class="border border-slate-300 px-2 py-1 text-center">8.00</td></tr>
                                            <tr><td class="border border-slate-300 px-2 py-1">Anios de constitucion</td><td class="border border-slate-300 px-2 py-1 text-center">35.00</td><td class="border border-slate-300 px-2 py-1 text-center">23.00</td><td class="border border-slate-300 px-2 py-1 text-center">8.00</td></tr>
                                            <tr class="bg-slate-100 font-semibold"><td class="border border-slate-300 px-2 py-1">Total</td><td class="border border-slate-300 px-2 py-1 text-center">325.00</td><td class="border border-slate-300 px-2 py-1 text-center">101.00</td><td class="border border-slate-300 px-2 py-1 text-center">45.00</td></tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="space-y-4">
                                    <div class="rounded-md border border-blue-200 bg-blue-50 p-3">
                                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-blue-700">Matriz de indicador "Zona geografica"</h3>
                                        <table class="w-full border border-slate-300 text-xs">
                                            <thead><tr class="bg-slate-100"><th class="border border-slate-300 px-2 py-1 text-left">Categoria</th><th class="border border-slate-300 px-2 py-1">Alto</th><th class="border border-slate-300 px-2 py-1">Medio</th><th class="border border-slate-300 px-2 py-1">Bajo</th></tr></thead>
                                            <tbody>
                                                <tr><td class="border border-slate-300 px-2 py-1">Sucursales</td><td class="border border-slate-300 px-2 py-1 text-center">3.0</td><td class="border border-slate-300 px-2 py-1 text-center">3.0</td><td class="border border-slate-300 px-2 py-1 text-center">3.0</td></tr>
                                                <tr class="bg-slate-100 font-semibold"><td class="border border-slate-300 px-2 py-1">Total</td><td class="border border-slate-300 px-2 py-1 text-center">3.0</td><td class="border border-slate-300 px-2 py-1 text-center">3.0</td><td class="border border-slate-300 px-2 py-1 text-center">3.0</td></tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="rounded-md border border-amber-200 bg-amber-50 p-3">
                                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-700">Matriz de indicador "Transacciones y canales"</h3>
                                        <table class="w-full border border-slate-300 text-xs">
                                            <thead><tr class="bg-slate-100"><th class="border border-slate-300 px-2 py-1 text-left">Categoria</th><th class="border border-slate-300 px-2 py-1">Alto</th><th class="border border-slate-300 px-2 py-1">Medio</th><th class="border border-slate-300 px-2 py-1">Bajo</th></tr></thead>
                                            <tbody>
                                                <tr><td class="border border-slate-300 px-2 py-1">Instrumento monetario</td><td class="border border-slate-300 px-2 py-1 text-center">30.00</td><td class="border border-slate-300 px-2 py-1 text-center">11.00</td><td class="border border-slate-300 px-2 py-1 text-center">7.50</td></tr>
                                                <tr><td class="border border-slate-300 px-2 py-1">Moneda divisa</td><td class="border border-slate-300 px-2 py-1 text-center">30.00</td><td class="border border-slate-300 px-2 py-1 text-center">18.00</td><td class="border border-slate-300 px-2 py-1 text-center">7.50</td></tr>
                                                <tr><td class="border border-slate-300 px-2 py-1">Canal de pago</td><td class="border border-slate-300 px-2 py-1 text-center">30.00</td><td class="border border-slate-300 px-2 py-1 text-center">17.00</td><td class="border border-slate-300 px-2 py-1 text-center">7.50</td></tr>
                                                <tr><td class="border border-slate-300 px-2 py-1">Monto Apertura</td><td class="border border-slate-300 px-2 py-1 text-center">30.00</td><td class="border border-slate-300 px-2 py-1 text-center">17.00</td><td class="border border-slate-300 px-2 py-1 text-center">7.50</td></tr>
                                                <tr><td class="border border-slate-300 px-2 py-1">Numero Pagos_Frecuencia</td><td class="border border-slate-300 px-2 py-1 text-center">30.00</td><td class="border border-slate-300 px-2 py-1 text-center">17.00</td><td class="border border-slate-300 px-2 py-1 text-center">7.50</td></tr>
                                                <tr class="bg-slate-100 font-semibold"><td class="border border-slate-300 px-2 py-1">Total</td><td class="border border-slate-300 px-2 py-1 text-center">150.00</td><td class="border border-slate-300 px-2 py-1 text-center">80.00</td><td class="border border-slate-300 px-2 py-1 text-center">37.50</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
            <template v-else-if="isAlertasAnonimasView">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <h2 class="mb-4 text-xl font-semibold text-slate-900 dark:text-slate-100">Alertas anonimas</h2>

                    <div class="mb-4 flex gap-2 border-b border-slate-200 pb-3 dark:border-slate-800">
                        <button
                            type="button"
                            class="rounded-md px-3 py-1 text-xs font-medium transition-colors"
                            :class="alertasTab === 'buzon' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'"
                            @click="setAlertasTab('buzon')"
                        >
                            Buzon de denuncia
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-3 py-1 text-xs font-medium transition-colors"
                            :class="alertasTab === 'reportes' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'"
                            @click="setAlertasTab('reportes')"
                        >
                            Ver reportes
                        </button>
                    </div>

                    <div v-if="alertasTab === 'buzon'" class="space-y-3">
                        <input
                            v-model="alertaAnonimaForm.destinatario"
                            type="text"
                            placeholder="Destinatario"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        />
                        <input
                            v-model="alertaAnonimaForm.asunto"
                            type="text"
                            placeholder="Asunto"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        />
                        <input
                            v-model="alertaAnonimaForm.empleado_reportado"
                            type="text"
                            placeholder="Nombre del empleado a reportar"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        />
                        <input
                            v-model="alertaAnonimaForm.motivo"
                            type="text"
                            placeholder="Motivo"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        />
                        <textarea
                            v-model="alertaAnonimaForm.detalle"
                            rows="3"
                            placeholder="Detalle del motivo"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        />
                        <textarea
                            v-model="alertaAnonimaForm.denuncia"
                            rows="7"
                            placeholder="Denuncia"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        />
                        <div class="flex justify-end">
                            <button
                                type="button"
                                class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                                :disabled="isSavingAlertaAnonima"
                                @click="submitAlertaAnonima"
                            >
                                {{ isSavingAlertaAnonima ? 'Enviando...' : 'Enviar mensaje' }}
                            </button>
                        </div>
                    </div>

                    <div v-else>
                        <a-table
                            :dataSource="reportesAnonimos"
                            :loading="isLoadingReportesAnonimos"
                            :pagination="false"
                            :locale="{ emptyText: 'Sin reportes registrados' }"
                            rowKey="id"
                            bordered
                        >
                            <a-table-column key="created_at" title="Fecha" data-index="created_at" :width="170" />
                            <a-table-column key="empleado_reportado" title="Empleado reportado" data-index="empleado_reportado" :width="200" />
                            <a-table-column key="motivo" title="Motivo" data-index="motivo" :width="180" />
                            <a-table-column key="denuncia" title="Denuncia" data-index="denuncia" />
                            <a-table-column key="estado" title="Estado" :width="160">
                                <template #default="{ record }">
                                    <span
                                        :class="record.is_leida ? 'rounded bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700' : 'rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700'"
                                    >
                                        {{ record.is_leida ? 'Leida' : 'Pendiente' }}
                                    </span>
                                </template>
                            </a-table-column>
                            <a-table-column key="acciones" title="Acciones" :width="220">
                                <template #default="{ record }">
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="rounded bg-emerald-600 px-2 py-1 text-xs text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="record.is_leida || isMarkingReporteById[record.id]"
                                            @click="markReporteAsRead(record.id)"
                                        >
                                            {{
                                                record.is_leida
                                                    ? 'Ya leida'
                                                    : (isMarkingReporteById[record.id] ? 'Guardando...' : 'Marcar leida')
                                            }}
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded bg-red-600 px-2 py-1 text-xs text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="isDeletingReporteById[record.id]"
                                            @click="deleteReporteAnonimo(record.id)"
                                        >
                                            {{ isDeletingReporteById[record.id] ? 'Eliminando...' : 'Eliminar' }}
                                        </button>
                                    </div>
                                </template>
                            </a-table-column>
                        </a-table>
                    </div>
                </div>
            </template>
            <template v-else-if="isAlertasView">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <h2 class="mb-4 text-xl font-semibold text-slate-900 dark:text-slate-100">Alertas</h2>

                    <div class="mb-4 flex items-center justify-between rounded-md border border-sky-100 bg-sky-50 px-3 py-2 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200">
                        <span>Ultima generacion: {{ alertasGeneratedLabel }}</span>
                        <button
                            type="button"
                            class="rounded bg-sky-700 px-2 py-1 text-[11px] text-white hover:bg-sky-800"
                        >
                            Ver historico de alertas
                        </button>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-[210px_1fr]">
                        <div class="space-y-2 rounded-md border border-slate-200 bg-slate-50 p-2 dark:border-slate-700 dark:bg-slate-900/50">
                            <button
                                v-for="section in alertasSections"
                                :key="section.id"
                                type="button"
                                class="w-full rounded px-3 py-2 text-left text-xs font-medium transition-colors"
                                :class="alertasVerticalTab === section.id
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-white text-slate-700 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'"
                                @click="alertasVerticalTab = section.id"
                            >
                                <span class="flex items-center justify-between gap-1">
                                    <span>{{ section.label }}</span>
                                    <span
                                        v-if="section.id === 'listas-negras-pep' && alertasListaNegraNoLeidas > 0"
                                        class="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-bold text-white"
                                    >
                                        {{ alertasListaNegraNoLeidas }}
                                    </span>
                                </span>
                            </button>
                            <button
                                type="button"
                                class="w-full rounded px-3 py-2 text-left text-xs font-medium transition-colors"
                                :class="alertasVerticalTab === 'descargar-xml'
                                    ? 'bg-emerald-600 text-white'
                                    : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-300 dark:hover:bg-emerald-900/50'"
                                @click="alertasVerticalTab = 'descargar-xml'"
                            >
                                Descargar XML
                            </button>
                        </div>

                        <div class="rounded-md border border-slate-200 p-3 dark:border-slate-700">
                            <template v-if="alertasVerticalTab === 'descargar-xml'">
                                <h3 class="mb-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                                    Descarga de Alertas en Formato XML
                                </h3>
                                <p class="mb-4 text-xs text-slate-700 dark:text-slate-300">
                                    Descarga el XML con la misma estructura de la tabla de Operaciones Relevantes (32 columnas).
                                </p>

                                <div class="space-y-3">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-700 dark:text-slate-300">
                                            Cliente
                                        </label>
                                        <select
                                            v-model="selectedXmlClientId"
                                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                        >
                                            <option :value="null" disabled>Selecciona un cliente</option>
                                            <option
                                                v-for="client in selectorClients"
                                                :key="client.numero_cliente"
                                                :value="client.numero_cliente"
                                            >
                                                {{ client.numero_cliente }} - {{ client.cliente }}
                                            </option>
                                        </select>
                                    </div>

                                    <button
                                        type="button"
                                        class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="isDownloadingXml"
                                        @click="downloadAlertasXml"
                                    >
                                        {{ isDownloadingXml ? 'Generando XML...' : 'Descargar XML' }}
                                    </button>

                                    <button
                                        type="button"
                                        class="w-full rounded-md bg-sky-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="isDownloadingXmlRules"
                                        @click="downloadAlertasXmlRules"
                                    >
                                        {{ isDownloadingXmlRules ? 'Generando XML (reglas)...' : 'Descargar XML (reglas)' }}
                                    </button>
                                </div>
                            </template>
                            <template v-else>
                                <h3 class="mb-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                                    {{ currentAlertasSection.titulo }}
                                </h3>
                                <p
                                    v-if="currentAlertasSection.descripcion"
                                    class="mb-3 text-xs text-slate-700 dark:text-slate-300"
                                >
                                    {{ currentAlertasSection.descripcion }}
                                </p>

                                <ul
                                    v-if="currentAlertasSection.bullets.length"
                                    class="mb-4 list-disc space-y-1 pl-4 text-xs text-slate-700 dark:text-slate-300"
                                >
                                    <li v-for="item in currentAlertasSection.bullets" :key="item">{{ item }}</li>
                                </ul>

                                <!-- Sección especial: Listas Negras / PEP -->
                                <template v-if="alertasVerticalTab === 'listas-negras-pep'">
                                    <div v-if="isLoadingListaNegra" class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                                        Cargando alertas...
                                    </div>
                                    <template v-else>
                                        <div v-if="alertasListaNegra.length === 0" class="rounded bg-sky-50 px-4 py-3 text-center text-sm font-semibold text-sky-900 dark:bg-slate-800 dark:text-sky-200">
                                            !Sin alertas registradas!
                                        </div>
                                        <template v-else>
                                            <div class="mb-3 flex items-center gap-2">
                                                <span v-if="alertasListaNegraNoLeidas > 0" class="rounded-full bg-rose-500 px-2 py-0.5 text-xs font-bold text-white">
                                                    {{ alertasListaNegraNoLeidas }} sin leer
                                                </span>
                                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                                    {{ alertasListaNegra.length }} alerta(s) total
                                                </span>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-xs">
                                                    <thead>
                                                        <tr class="border-b border-slate-200 dark:border-slate-700 text-left">
                                                            <th class="pb-2 pr-3 font-semibold text-slate-600 dark:text-slate-300">Nombre detectado</th>
                                                            <th class="pb-2 pr-3 font-semibold text-slate-600 dark:text-slate-300">Excel origen</th>
                                                            <th class="pb-2 pr-3 font-semibold text-slate-600 dark:text-slate-300">Encontrado en lista</th>
                                                            <th class="pb-2 pr-3 font-semibold text-slate-600 dark:text-slate-300">Fecha</th>
                                                            <th class="pb-2 font-semibold text-slate-600 dark:text-slate-300">Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="alerta in alertasListaNegra"
                                                            :key="alerta.id"
                                                            :class="[
                                                                'border-b border-slate-100 dark:border-slate-800',
                                                                alerta.is_leida ? 'opacity-60' : 'bg-rose-50 dark:bg-rose-900/10'
                                                            ]"
                                                        >
                                                            <td class="py-2 pr-3 font-medium text-slate-800 dark:text-slate-100">
                                                                <span v-if="!alerta.is_leida" class="mr-1 inline-block h-2 w-2 rounded-full bg-rose-500"></span>
                                                                {{ alerta.valor_detectado }}
                                                            </td>
                                                            <td class="py-2 pr-3 text-slate-600 dark:text-slate-400">{{ alerta.excel_name_origen }}</td>
                                                            <td class="py-2 pr-3 text-slate-600 dark:text-slate-400">{{ alerta.nombre_lista }}</td>
                                                            <td class="py-2 pr-3 text-slate-500 dark:text-slate-500 whitespace-nowrap">
                                                                {{ new Date(alerta.created_at).toLocaleDateString('es-MX') }}
                                                            </td>
                                                            <td class="py-2">
                                                                <div class="flex items-center gap-1">
                                                                    <button
                                                                        v-if="!alerta.is_leida"
                                                                        type="button"
                                                                        :disabled="isMarkingListaNegraById[alerta.id]"
                                                                        @click="markListaNegraAsRead(alerta.id)"
                                                                        class="rounded bg-sky-600 px-2 py-1 text-[10px] text-white hover:bg-sky-700 disabled:opacity-50"
                                                                    >
                                                                        Marcar leída
                                                                    </button>
                                                                    <button
                                                                        type="button"
                                                                        :disabled="isDeletingListaNegraById[alerta.id]"
                                                                        @click="deleteAlertaListaNegra(alerta.id)"
                                                                        class="rounded bg-slate-200 px-2 py-1 text-[10px] text-slate-700 hover:bg-rose-100 hover:text-rose-700 disabled:opacity-50 dark:bg-slate-700 dark:text-slate-300"
                                                                    >
                                                                        Eliminar
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </template>
                                    </template>
                                </template>

                                <!-- Resto de secciones: mensaje genérico -->
                                <div v-else class="rounded bg-sky-50 px-4 py-3 text-center text-sm font-semibold text-sky-900 dark:bg-slate-800 dark:text-sky-200">
                                    !Sin alertas registradas!
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else-if="isSystemUsersView">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Usuarios del sistema</h2>
                        <button
                            type="button"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                            @click="openCreateSystemUserModal"
                        >
                            Nuevo usuario
                        </button>
                    </div>

                    <div class="mb-4">
                        <input
                            v-model="systemUserSearch"
                            type="text"
                            placeholder="Buscar por nombre o correo..."
                            @input="loadSystemUsers"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        />
                    </div>

                    <a-table
                        :dataSource="systemUsers"
                        :loading="isLoadingSystemUsers"
                        :pagination="false"
                        :locale="{ emptyText: 'Sin usuarios registrados' }"
                        rowKey="id"
                        bordered
                    >
                        <a-table-column key="id" title="ID" data-index="id" :width="70" />
                        <a-table-column key="name" title="Nombre" data-index="name" />
                        <a-table-column key="email" title="Correo" data-index="email" />
                        <a-table-column key="role" title="Rol" :width="130">
                            <template #default="{ record }">
                                <span class="text-xs">{{ record.role || 'Sin rol' }}</span>
                            </template>
                        </a-table-column>
                        <a-table-column key="status" title="Estado" :width="100">
                            <template #default="{ record }">
                                <span class="text-xs">{{ record.status === 1 ? 'Activo' : 'Inactivo' }}</span>
                            </template>
                        </a-table-column>
                        <a-table-column key="acciones" title="Acciones" :width="220">
                            <template #default="{ record }">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="rounded bg-amber-500 px-2 py-1 text-xs text-white hover:bg-amber-600"
                                        @click="openEditSystemUserModal(record)"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded bg-slate-700 px-2 py-1 text-xs text-white hover:bg-slate-800"
                                        @click="openSystemUserPasswordModal(record)"
                                    >
                                        Contraseña
                                    </button>
                                </div>
                            </template>
                        </a-table-column>
                    </a-table>
                </div>

                <div
                    v-if="isSystemUserModalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                >
                    <div class="w-full max-w-xl rounded-lg bg-white shadow-xl dark:bg-slate-900">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">
                                {{ systemUserModalMode === 'create' ? 'Nuevo usuario' : 'Editar usuario' }}
                            </h3>
                            <button
                                type="button"
                                class="rounded bg-slate-200 px-2 py-1 text-xs text-slate-800 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600"
                                @click="closeSystemUserModal"
                            >
                                Cerrar
                            </button>
                        </div>

                        <div class="space-y-3 p-4">
                            <input
                                v-model="systemUserForm.name"
                                type="text"
                                placeholder="Nombre"
                                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            />
                            <input
                                v-model="systemUserForm.email"
                                type="email"
                                placeholder="Correo electrónico"
                                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            />
                            <div class="grid gap-3 sm:grid-cols-2">
                                <select
                                    v-model="systemUserForm.role"
                                    class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                >
                                    <option v-for="role in systemRoles" :key="role" :value="role">
                                        {{ role }}
                                    </option>
                                </select>
                                <select
                                    v-model="systemUserForm.status"
                                    class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                >
                                    <option :value="1">Activo</option>
                                    <option :value="2">Inactivo</option>
                                </select>
                            </div>

                            <template v-if="systemUserModalMode === 'create'">
                                <input
                                    v-model="systemUserForm.password"
                                    type="password"
                                    placeholder="Contraseña"
                                    class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                />
                                <input
                                    v-model="systemUserForm.password_confirmation"
                                    type="password"
                                    placeholder="Confirmar contraseña"
                                    class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                />
                            </template>
                        </div>

                        <div class="flex justify-end gap-2 border-t border-slate-200 px-4 py-3 dark:border-slate-700">
                            <button
                                type="button"
                                class="rounded bg-slate-200 px-3 py-2 text-xs text-slate-800 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600"
                                @click="closeSystemUserModal"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="rounded bg-blue-600 px-3 py-2 text-xs text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="isSavingSystemUser"
                                @click="saveSystemUser"
                            >
                                {{ isSavingSystemUser ? 'Guardando...' : 'Guardar' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="isSystemUserPasswordModalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                >
                    <div class="w-full max-w-lg rounded-lg bg-white shadow-xl dark:bg-slate-900">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">
                                Cambiar contraseña de {{ passwordTargetUser?.name || 'usuario' }}
                            </h3>
                            <button
                                type="button"
                                class="rounded bg-slate-200 px-2 py-1 text-xs text-slate-800 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600"
                                @click="closeSystemUserPasswordModal"
                            >
                                Cerrar
                            </button>
                        </div>

                        <div class="space-y-3 p-4">
                            <input
                                v-model="systemUserPasswordForm.password"
                                type="password"
                                placeholder="Nueva contraseña"
                                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            />
                            <input
                                v-model="systemUserPasswordForm.password_confirmation"
                                type="password"
                                placeholder="Confirmar contraseña"
                                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            />
                        </div>

                        <div class="flex justify-end gap-2 border-t border-slate-200 px-4 py-3 dark:border-slate-700">
                            <button
                                type="button"
                                class="rounded bg-slate-200 px-3 py-2 text-xs text-slate-800 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600"
                                @click="closeSystemUserPasswordModal"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="rounded bg-blue-600 px-3 py-2 text-xs text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="isSavingSystemUserPassword"
                                @click="updateSystemUserPassword"
                            >
                                {{ isSavingSystemUserPassword ? 'Guardando...' : 'Actualizar contraseña' }}
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="mb-8 text-center">
                    <h1 class="title-system">
                        Sistema Automátizado en Prevención de Lavado de Dinero y Financiamiento al Terrorismo
                    </h1>
                </div>

                <div class="mb-6 text-center">
                    <h2 class="text-xl font-semibold text-gray-600 dark:text-gray-300">
                        Buscador de Personas
                    </h2>
                </div>
                <div class="mb-6 flex justify-center">
                    <div class="relative w-full max-w-2xl">
                        <div class="relative flex items-center">
                            <input v-model="searchQuery" type="text" placeholder="Buscar..." @keydown.enter="handleSearch"
                                class="w-full rounded-full border border-gray-300 bg-white px-6 py-4 pr-16 text-lg shadow-lg transition-all duration-200 ease-in-out focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-blue-400 dark:focus:ring-blue-400/20" />
                            <button type="button" @click="handleSearch"
                                class="absolute right-2 flex h-10 w-10 items-center justify-center rounded-full bg-blue-500 text-white transition-all duration-200 ease-in-out hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 dark:bg-blue-400 dark:hover:bg-blue-500">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </div>
                        <div
                            class="absolute inset-0 -z-10 rounded-full bg-gradient-to-r from-blue-500/10 via-purple-500/10 to-pink-500/10 blur-xl dark:from-blue-400/20 dark:via-purple-400/20 dark:to-pink-400/20">
                        </div>
                    </div>
                </div>

                <!-- Aviso de coincidencia en Lista Negra/PEP -->
                <transition name="fade">
                    <div
                        v-if="searchListaNegraAlerta.visible"
                        class="mb-4 flex items-start gap-3 rounded-xl border border-red-400/40 bg-red-500/10 px-5 py-4 text-red-200 shadow-lg"
                    >
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                        <div>
                            <p class="font-bold text-red-300">⚠ Coincidencia en Lista Negra / PEP</p>
                            <p class="text-sm text-red-200/80 mt-0.5">
                                Se encontraron {{ searchListaNegraAlerta.count }} coincidencia(s) en archivos de Lista Negra/PEP.
                                Revisa la sección <strong>Alertas → Listas Negras Y De Personas Políticamente Expuestas</strong>.
                            </p>
                        </div>
                    </div>
                </transition>

                <div class="mt-3">
                    <div class="card mt-3 px-0">
                        <div class="card-body p-0">
                            <a-table :scroll="{ x: true }" :dataSource="data"
                                :locale="{ emptyText: 'Sin datos' }" :pagination="false" bordered
                                class="ant-table-striped dashboard-jade-table"
                                :row-class-name="(_record: any, index: number) => index % 2 === 1 ? 'table-striped' : null">
                                <a-table-column key="id" title="ID" data-index="id" :sorter="false"
                                    :showSorterTooltip="false">
                                    <template #default="{ record }">
                                        <span>
                                            {{ record.id }}
                                        </span>
                                    </template>
                                </a-table-column>

                                <a-table-column key="value" title="Dato" data-index="value" :sorter="false"
                                    :showSorterTooltip="false">
                                    <template #default="{ record }">
                                        <span>
                                            {{ record.value }}
                                        </span>
                                    </template>
                                </a-table-column>

                                <a-table-column key="file" title="Archivo" data-index="file" :sorter="false"
                                    :showSorterTooltip="false">
                                    <template #default="{ record }">
                                        <span>
                                            {{ record.excel_name }}
                                        </span>
                                    </template>
                                </a-table-column>
                            </a-table>
                        </div>
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-12 text-center mt-5">
                                    <a-pagination class="dashboard-jade-pagination" :show-total="(total: number, range: number[]) =>
                                            `${range[0]} a ${range[1]} de ${numeralFormat(total, '0,0')} resultados`
                                        " :pageSizeOptions="['10', '20', '50', '100']" v-model:current="current"
                                        v-model:page-size="pageSize" :total="total" show-size-changer
                                        @showSizeChange="onShowSizeChange" @change="onChangePage">
                                        <template #buildOptionText="props">
                                            <span>{{ props.value }} / Página</span>
                                        </template>
                                    </a-pagination>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
