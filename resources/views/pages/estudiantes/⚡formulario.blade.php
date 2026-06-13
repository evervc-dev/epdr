<?php

use Livewire\Component;
use App\Models\Estudiante;
use App\Models\TutorFamiliar;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public ?int $estudianteId = null;

    // Student fields
    public string $nie = '';
    public string $nombres = '';
    public string $apellidos = '';
    public ?string $fecha_nacimiento = null;
    public string $genero = 'M';
    public bool $es_repitente = false;
    public bool $tiene_extraedad = false;
    public bool $pertenece_dai = false;
    public string $actividad_economica = 'No trabaja';
    public string $convivencia = 'Vive con ambos';

    // Tutores list
    public array $tutores = [];

    // Modal state for adding a tutor
    public bool $modalTutorAbierto = false;
    public ?int $tutorEditIndex = null;

    // Tutor form fields
    public string $tutor_nombre = '';
    public string $tutor_parentesco = 'Madre';
    public string $tutor_nivel_academico = 'Educación Básica';
    public string $tutor_situacion_laboral = 'Empleado';
    public string $tutor_telefono = '';
    public bool $tutor_es_contacto_principal = false;

    public function mount($id = null)
    {
        if ($id) {
            abort_unless(auth()->user()->can('estudiantes.editar'), 403);

            $estudiante = Estudiante::findOrFail($id);
            $this->estudianteId = $estudiante->id;
            $this->nie = $estudiante->nie;
            $this->nombres = $estudiante->nombres;
            $this->apellidos = $estudiante->apellidos;
            $this->fecha_nacimiento = $estudiante->fecha_nacimiento->format('Y-m-d');
            $this->genero = $estudiante->genero;
            $this->es_repitente = $estudiante->es_repitente;
            $this->tiene_extraedad = $estudiante->tiene_extraedad;
            $this->pertenece_dai = $estudiante->pertenece_dai;
            $this->actividad_economica = $estudiante->actividad_economica;
            $this->convivencia = $estudiante->convivencia;

            foreach ($estudiante->tutores as $tutor) {
                $this->tutores[] = [
                    'id' => $tutor->id,
                    'nombre' => $tutor->nombre,
                    'parentesco' => $tutor->parentesco,
                    'nivel_academico' => $tutor->nivel_academico,
                    'situacion_laboral' => $tutor->situacion_laboral,
                    'telefono' => $tutor->telefono,
                    'es_contacto_principal' => (bool)$tutor->pivot->es_contacto_principal,
                ];
            }
        } else {
            abort_unless(auth()->user()->can('estudiantes.crear'), 403);
        }
    }

    public function abrirModalTutor()
    {
        $this->tutorEditIndex = null;
        $this->tutor_nombre = '';
        $this->tutor_parentesco = 'Madre';
        $this->tutor_nivel_academico = 'Educación Básica';
        $this->tutor_situacion_laboral = 'Empleado';
        $this->tutor_telefono = '';
        $this->tutor_es_contacto_principal = count($this->tutores) === 0;

        $this->modalTutorAbierto = true;
    }

    public function editarTutor($index)
    {
        $this->tutorEditIndex = $index;
        $tutor = $this->tutores[$index];
        $this->tutor_nombre = $tutor['nombre'];
        $this->tutor_parentesco = $tutor['parentesco'];
        $this->tutor_nivel_academico = $tutor['nivel_academico'];
        $this->tutor_situacion_laboral = $tutor['situacion_laboral'];
        $this->tutor_telefono = $tutor['telefono'] ?? '';
        $this->tutor_es_contacto_principal = $tutor['es_contacto_principal'];

        $this->modalTutorAbierto = true;
    }

    public function guardarTutor()
    {
        $rules = [
            'tutor_nombre' => 'required|string|max:100',
            'tutor_parentesco' => 'required|in:Madre,Padre,Abuela,Abuelo,Tía,Tío,Hermana,Hermano,Madrastra,Padrastro,Tutora legal,Tutor legal,Otro',
            'tutor_nivel_academico' => 'required|in:No sabe leer/escribir,Educación Básica,Educación Media,Educación Superior',
            'tutor_situacion_laboral' => 'required|in:Empleado,Comerciante,Oficios varios,No trabaja',
            'tutor_telefono' => 'nullable|string|max:20',
        ];

        $messages = [
            'tutor_nombre.required' => 'El nombre del tutor es obligatorio.',
            'tutor_parentesco.required' => 'El parentesco es obligatorio.',
            'tutor_nivel_academico.required' => 'El nivel académico es obligatorio.',
            'tutor_situacion_laboral.required' => 'La situación laboral es obligatoria.',
        ];

        $this->validate($rules, $messages);

        $tutorData = [
            'id' => $this->tutorEditIndex !== null ? ($this->tutores[$this->tutorEditIndex]['id'] ?? null) : null,
            'nombre' => $this->tutor_nombre,
            'parentesco' => $this->tutor_parentesco,
            'nivel_academico' => $this->tutor_nivel_academico,
            'situacion_laboral' => $this->tutor_situacion_laboral,
            'telefono' => $this->tutor_telefono,
            'es_contacto_principal' => $this->tutor_es_contacto_principal,
        ];

        if ($this->tutor_es_contacto_principal) {
            foreach ($this->tutores as $key => $tutor) {
                if ($this->tutorEditIndex === null || $key !== $this->tutorEditIndex) {
                    $this->tutores[$key]['es_contacto_principal'] = false;
                }
            }
        }

        if ($this->tutorEditIndex !== null) {
            $this->tutores[$this->tutorEditIndex] = $tutorData;
        } else {
            $this->tutores[] = $tutorData;
        }

        $this->modalTutorAbierto = false;
    }

    public function desvincularTutor($index)
    {
        unset($this->tutores[$index]);
        $this->tutores = array_values($this->tutores);
    }

    public function guardar()
    {
        if ($this->estudianteId) {
            abort_unless(auth()->user()->can('estudiantes.editar'), 403);
        } else {
            abort_unless(auth()->user()->can('estudiantes.crear'), 403);
        }

        $rules = [
            'nie' => [
                'required',
                'string',
                'max:20',
                Rule::unique('estudiantes', 'nie')->ignore($this->estudianteId),
            ],
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'fecha_nacimiento' => 'required|date|before:today',
            'genero' => 'required|in:M,F',
            'actividad_economica' => 'required|string',
            'convivencia' => 'required|string',
        ];

        $messages = [
            'nie.required' => 'El NIE es obligatorio.',
            'nie.unique' => 'Este NIE ya está registrado en el sistema.',
            'nombres.required' => 'Los nombres son obligatorios.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior al día de hoy.',
            'genero.required' => 'El género es obligatorio.',
        ];

        $this->validate($rules, $messages);

        if (count($this->tutores) === 0) {
            $this->addError('tutores', 'Debe agregar al menos un tutor familiar.');
            return;
        }

        $primaryContacts = collect($this->tutores)->filter(fn($t) => $t['es_contacto_principal'] === true);
        if ($primaryContacts->count() !== 1) {
            $this->addError('tutores', 'Debe marcar exactamente un tutor como contacto principal.');
            return;
        }

        DB::transaction(function () {
            if ($this->estudianteId) {
                $estudiante = Estudiante::findOrFail($this->estudianteId);
                $estudiante->update([
                    'nie' => $this->nie,
                    'nombres' => $this->nombres,
                    'apellidos' => $this->apellidos,
                    'fecha_nacimiento' => $this->fecha_nacimiento,
                    'genero' => $this->genero,
                    'es_repitente' => $this->es_repitente,
                    'tiene_extraedad' => $this->tiene_extraedad,
                    'pertenece_dai' => $this->pertenece_dai,
                    'actividad_economica' => $this->actividad_economica,
                    'convivencia' => $this->convivencia,
                ]);
            } else {
                $estudiante = Estudiante::create([
                    'nie' => $this->nie,
                    'nombres' => $this->nombres,
                    'apellidos' => $this->apellidos,
                    'fecha_nacimiento' => $this->fecha_nacimiento,
                    'genero' => $this->genero,
                    'es_repitente' => $this->es_repitente,
                    'tiene_extraedad' => $this->tiene_extraedad,
                    'pertenece_dai' => $this->pertenece_dai,
                    'actividad_economica' => $this->actividad_economica,
                    'convivencia' => $this->convivencia,
                ]);
            }

            $syncData = [];
            foreach ($this->tutores as $tData) {
                if (!empty($tData['id'])) {
                    $tutor = TutorFamiliar::findOrFail($tData['id']);
                    $tutor->update([
                        'nombre' => $tData['nombre'],
                        'parentesco' => $tData['parentesco'],
                        'nivel_academico' => $tData['nivel_academico'],
                        'situacion_laboral' => $tData['situacion_laboral'],
                        'telefono' => $tData['telefono'],
                    ]);
                } else {
                    $tutor = TutorFamiliar::create([
                        'nombre' => $tData['nombre'],
                        'parentesco' => $tData['parentesco'],
                        'nivel_academico' => $tData['nivel_academico'],
                        'situacion_laboral' => $tData['situacion_laboral'],
                        'telefono' => $tData['telefono'],
                    ]);
                }

                $syncData[$tutor->id] = ['es_contacto_principal' => $tData['es_contacto_principal']];
            }

            $estudiante->tutores()->sync($syncData);
        });

        $this->dispatch('notify', message: $this->estudianteId ? 'Estudiante actualizado con éxito.' : 'Estudiante registrado con éxito.', type: 'success');

        return redirect()->route('estudiantes.index');
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.app')
            ->title(($this->estudianteId ? 'Editar Estudiante' : 'Nuevo Estudiante') . ' | SIG');
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">
                {{ $estudianteId ? 'Editar Estudiante' : 'Nuevo Estudiante' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $estudianteId ? 'Modifique los campos y configure los tutores del estudiante.' : 'Complete los datos de caracterización del nuevo estudiante.' }}
            </p>
        </div>
        <div>
            <a
                href="{{ route('estudiantes.index') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-white hover:bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 border border-slate-300 shadow-2xs transition"
            >
                Cancelar y Volver
            </a>
        </div>
    </div>

    <form wire:submit.prevent="guardar" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Student data column (2/3 width on large screens) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-6 space-y-6">
                <h2 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3">Información General</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- NIE -->
                    <div>
                        <label for="nie" class="block text-sm font-semibold text-slate-700">NIE (Número de Identificación Estudiantil)</label>
                        <input
                            type="text"
                            id="nie"
                            wire:model="nie"
                            placeholder="Ej: 1029384"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="nie" />
                    </div>

                    <!-- Género -->
                    <div>
                        <label for="genero" class="block text-sm font-semibold text-slate-700">Género</label>
                        <select
                            id="genero"
                            wire:model="genero"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        >
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                        <x-input-error field="genero" />
                    </div>

                    <!-- Nombres -->
                    <div>
                        <label for="nombres" class="block text-sm font-semibold text-slate-700">Nombres</label>
                        <input
                            type="text"
                            id="nombres"
                            wire:model="nombres"
                            placeholder="Nombres del estudiante"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="nombres" />
                    </div>

                    <!-- Apellidos -->
                    <div>
                        <label for="apellidos" class="block text-sm font-semibold text-slate-700">Apellidos</label>
                        <input
                            type="text"
                            id="apellidos"
                            wire:model="apellidos"
                            placeholder="Apellidos del estudiante"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="apellidos" />
                    </div>

                    <!-- Fecha de Nacimiento -->
                    <div>
                        <label for="fecha_nacimiento" class="block text-sm font-semibold text-slate-700">Fecha de Nacimiento</label>
                        <input
                            type="date"
                            id="fecha_nacimiento"
                            wire:model="fecha_nacimiento"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="fecha_nacimiento" />
                    </div>

                    <!-- Convivencia -->
                    <div>
                        <label for="convivencia" class="block text-sm font-semibold text-slate-700">Convivencia Familiar</label>
                        <select
                            id="convivencia"
                            wire:model="convivencia"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        >
                            <option value="Vive con ambos">Vive con ambos padres</option>
                            <option value="Vive con la madre">Vive con la madre</option>
                            <option value="Vive con el padre">Vive con el padre</option>
                            <option value="Vive con familiares">Vive con familiares</option>
                            <option value="No vive con familiares">No vive con familiares</option>
                        </select>
                        <x-input-error field="convivencia" />
                    </div>

                    <!-- Actividad Económica -->
                    <div>
                        <label for="actividad_economica" class="block text-sm font-semibold text-slate-700">Actividad Económica en que labora</label>
                        <select
                            id="actividad_economica"
                            wire:model="actividad_economica"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        >
                            <option value="No trabaja">No trabaja (Solo estudia)</option>
                            <option value="Agricultura">Agricultura / Cultivos</option>
                            <option value="Venta ambulante">Venta ambulante</option>
                            <option value="Trabajo doméstico">Trabajo doméstico</option>
                            <option value="Negocio familiar">Ayuda en negocio familiar</option>
                            <option value="Recolección">Recolección (café, caña, etc.)</option>
                            <option value="Pesca">Pesca / Mariscos</option>
                            <option value="Oficios varios">Oficios varios</option>
                            <option value="Otros">Otros</option>
                        </select>
                        <x-input-error field="actividad_economica" />
                    </div>
                </div>

                <!-- Vulnerabilidad académica -->
                <div class="border-t border-slate-100 pt-5 space-y-4">
                    <h3 class="text-sm font-bold text-slate-800">Indicadores de Vulnerabilidad Académica</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label class="relative flex items-start rounded-xl border border-slate-200 p-4 cursor-pointer hover:bg-slate-50 transition">
                            <div class="flex h-5 items-center">
                                <input type="checkbox" wire:model="es_repitente" class="h-4 w-4 rounded-sm border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            </div>
                            <div class="ml-3 text-sm">
                                <span class="font-bold text-slate-900">Repitente</span>
                                <p class="text-slate-500 text-2xs mt-0.5">El alumno está cursando el grado por segunda vez.</p>
                            </div>
                        </label>

                        <label class="relative flex items-start rounded-xl border border-slate-200 p-4 cursor-pointer hover:bg-slate-50 transition">
                            <div class="flex h-5 items-center">
                                <input type="checkbox" wire:model="tiene_extraedad" class="h-4 w-4 rounded-sm border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            </div>
                            <div class="ml-3 text-sm">
                                <span class="font-bold text-slate-900">Extraedad</span>
                                <p class="text-slate-500 text-2xs mt-0.5">La edad supera en 2 años o más al promedio del grado.</p>
                            </div>
                        </label>

                        <label class="relative flex items-start rounded-xl border border-slate-200 p-4 cursor-pointer hover:bg-slate-50 transition">
                            <div class="flex h-5 items-center">
                                <input type="checkbox" wire:model="pertenece_dai" class="h-4 w-4 rounded-sm border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            </div>
                            <div class="ml-3 text-sm">
                                <span class="font-bold text-slate-900">DAI</span>
                                <p class="text-slate-500 text-2xs mt-0.5">El estudiante pertenece al aula de Apoyo Inclusivo.</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tutores column (1/3 width on large screens) -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xs p-6 space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="text-lg font-bold text-slate-900">Tutores Familiares</h2>
                    <button
                        type="button"
                        wire:click="abrirModalTutor"
                        class="text-xs font-bold text-indigo-600 hover:text-indigo-500 flex items-center gap-1"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Agregar
                    </button>
                </div>

                @error('tutores')
                    <div class="rounded-xl bg-rose-50 p-3 text-xs font-semibold text-rose-700 border border-rose-100">
                        {{ $message }}
                    </div>
                @enderror

                <div class="space-y-3">
                    @forelse($tutores as $index => $tutor)
                        <div class="relative group rounded-2xl border border-slate-200 p-4 hover:border-slate-300 transition bg-slate-50/30">
                            <!-- Options buttons -->
                            <div class="absolute top-4 right-4 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition duration-150">
                                <button
                                    type="button"
                                    wire:click="editarTutor({{ $index }})"
                                    class="p-1 text-slate-500 hover:text-indigo-600 hover:bg-white rounded-md transition shadow-2xs"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    wire:click="desvincularTutor({{ $index }})"
                                    class="p-1 text-slate-500 hover:text-rose-600 hover:bg-white rounded-md transition shadow-2xs"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-slate-900">{{ $tutor['nombre'] }}</span>
                                    @if($tutor['es_contacto_principal'])
                                        <span class="inline-flex items-center rounded-md bg-indigo-50 px-1.5 py-0.5 text-3xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-600/10">Principal</span>
                                    @endif
                                </div>
                                <div class="grid grid-cols-2 gap-y-1 text-xs text-slate-500">
                                    <div>Parentesco:</div>
                                    <div class="font-medium text-slate-700">{{ $tutor['parentesco'] }}</div>
                                    <div>Teléfono:</div>
                                    <div class="font-medium text-slate-700">{{ $tutor['telefono'] ?: 'No registrado' }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-slate-400 text-xs italic">
                            No se han agregado tutores. Debe vincular al menos un tutor familiar.
                        </div>
                    @endforelse
                </div>

                <div class="border-t border-slate-100 pt-5">
                    <button
                        type="submit"
                        class="w-full flex justify-center rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:shadow transition duration-150"
                    >
                        {{ $estudianteId ? 'Guardar Cambios' : 'Registrar Estudiante' }}
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Tutor Dialog Modal (Create / Edit) -->
    @if($modalTutorAbierto)
    <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" wire:click="$set('modalTutorAbierto', false)"></div>

        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-3xl bg-white px-6 pb-6 pt-6 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg w-full">
                <!-- Close Button -->
                <button
                    type="button"
                    wire:click="$set('modalTutorAbierto', false)"
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 transition"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <h3 class="text-xl font-bold text-slate-900 mb-6">
                    {{ $tutorEditIndex !== null ? 'Editar Tutor' : 'Agregar Tutor Familiar' }}
                </h3>

                <form wire:submit.prevent="guardarTutor" class="space-y-4">
                    <!-- Nombre Completo -->
                    <div>
                        <label for="tutor_nombre" class="block text-sm font-semibold text-slate-700">Nombre Completo</label>
                        <input
                            type="text"
                            id="tutor_nombre"
                            wire:model="tutor_nombre"
                            placeholder="Ej: María Gómez"
                            class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                        />
                        <x-input-error field="tutor_nombre" />
                    </div>

                    <!-- Parentesco -->
                        <div>
                            <label for="tutor_parentesco" class="block text-sm font-semibold text-slate-700">Parentesco con el estudiante</label>
                            <select
                                id="tutor_parentesco"
                                wire:model="tutor_parentesco"
                                class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                            >
                                <option value="Madre">Madre</option>
                                <option value="Padre">Padre</option>
                                <option value="Abuela">Abuela</option>
                                <option value="Abuelo">Abuelo</option>
                                <option value="Tía">Tía</option>
                                <option value="Tío">Tío</option>
                                <option value="Hermana">Hermana</option>
                                <option value="Hermano">Hermano</option>
                                <option value="Madrastra">Madrastra</option>
                                <option value="Padrastro">Padrastro</option>
                                <option value="Tutora legal">Tutora legal</option>
                                <option value="Tutor legal">Tutor legal</option>
                                <option value="Otro">Otro</option>
                            </select>
                            <x-input-error field="tutor_parentesco" />
                        </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Nivel Académico -->
                        <div>
                            <label for="tutor_nivel_academico" class="block text-sm font-semibold text-slate-700">Nivel Académico</label>
                            <select
                                id="tutor_nivel_academico"
                                wire:model="tutor_nivel_academico"
                                class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                            >
                                <option value="No sabe leer/escribir">No sabe leer/escribir</option>
                                <option value="Educación Básica">Educación Básica</option>
                                <option value="Educación Media">Educación Media</option>
                                <option value="Educación Superior">Educación Superior</option>
                            </select>
                            <x-input-error field="tutor_nivel_academico" />
                        </div>

                        <!-- Situación Laboral -->
                        <div>
                            <label for="tutor_situacion_laboral" class="block text-sm font-semibold text-slate-700">Situación Laboral</label>
                            <select
                                id="tutor_situacion_laboral"
                                wire:model="tutor_situacion_laboral"
                                class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                            >
                                <option value="Empleado">Empleado</option>
                                <option value="Comerciante">Comerciante</option>
                                <option value="Oficios varios">Oficios varios</option>
                                <option value="No trabaja">No trabaja</option>
                            </select>
                            <x-input-error field="tutor_situacion_laboral" />
                        </div>
                    </div>

                    <!-- Teléfono y Contacto Principal -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                        <div>
                            <label for="tutor_telefono" class="block text-sm font-semibold text-slate-700">Teléfono</label>
                            <input
                                type="text"
                                id="tutor_telefono"
                                wire:model="tutor_telefono"
                                placeholder="Ej: 7777-7777"
                                class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-950 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition"
                            />
                            <x-input-error field="tutor_telefono" />
                        </div>

                        <div class="pt-6">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="tutor_es_contacto_principal" class="h-4 w-4 rounded-sm border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="ml-2 text-sm font-semibold text-slate-700">Es contacto principal</span>
                            </label>
                            <x-input-error field="tutor_es_contacto_principal" />
                        </div>
                    </div>

                    <div class="mt-6 flex flex-row-reverse gap-2">
                        <button
                            type="submit"
                            class="inline-flex justify-center rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition"
                        >
                            Listo
                        </button>
                        <button
                            type="button"
                            wire:click="$set('modalTutorAbierto', false)"
                            class="inline-flex justify-center rounded-xl bg-white hover:bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 border border-slate-300 transition duration-150"
                        >
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
