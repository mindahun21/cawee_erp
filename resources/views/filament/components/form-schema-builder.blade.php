@php
    $fieldId = $getId();
    $statePath = $getStatePath();
    $availableFields = $getAvailableFields();
    $skillOptions = $getSkillOptions();
    $currentSchema = $getState() ?? [];

    if (is_string($currentSchema)) {
        $currentSchema = json_decode($currentSchema, true) ?? [];
    }
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        wire:ignore
        x-data="formSchemaBuilder({
            initialSchema: @js($currentSchema),
            availableFields: @js($availableFields),
            skillOptions: @js($skillOptions),
            statePath: @js($statePath),
        })"
        x-init="init()"
        class="form-schema-builder-root"
    >
        <input
            type="hidden"
            id="{{ $fieldId }}"
            name="{{ $statePath }}"
            x-ref="hiddenInput"
            x-bind:value="JSON.stringify(schemaForSave())"
        >

        <div class="fsb-toolbar">
            <button
                type="button"
                class="fsb-preview-btn"
                :disabled="items.length === 0"
                @click.prevent="openPreview()"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                Preview Form
            </button>
        </div>

        <div class="fsb-layout">
            {{-- LEFT: Field Palette --}}
            <div class="fsb-palette">
                <div class="fsb-palette-title">Available Fields</div>
                <template x-for="field in availableFields" :key="field.field_key">
                    <div
                        class="fsb-palette-item"
                        :class="{ 'is-disabled': !canAddField(field) }"
                        :draggable="canAddField(field)"
                        @dragstart="startPaletteDrag($event, field)"
                        @click.prevent="addField(field.field_key)"
                    >
                        <span class="fsb-palette-label" x-text="field.label"></span>
                    </div>
                </template>
            </div>

            {{-- RIGHT: Canvas --}}
            <div
                class="fsb-canvas"
                @dragover.prevent
                @drop.prevent="handleCanvasDrop($event)"
            >
                <template x-if="items.length === 0">
                    <div class="fsb-canvas-empty">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:.4"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        <span>Click or drag fields from the left to add them here</span>
                    </div>
                </template>

                <div x-ref="canvasList" class="fsb-canvas-list">
                    <template x-for="(item, index) in items" :key="item.instance_id">
                        <div class="fsb-item" :data-instance-id="item.instance_id">
                            {{-- Row header: [drag handle] [label] [edit] [delete] --}}
                            <div class="fsb-item-header" :class="{ 'is-open': item.isOpen }">
                                <button type="button" class="fsb-drag-handle js-drag-handle" title="Drag to reorder">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/></svg>
                                </button>
                                <div class="fsb-item-label" x-text="rowLabel(item)"></div>
                                <div class="fsb-item-actions">
                                    <button type="button" class="fsb-action-btn fsb-action-edit" @click.stop="toggleEdit(index)" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 19l4-1 9-9-3-3-9 9-1 4z"/><path d="M7 17L17 7"/></svg>
                                    </button>
                                    <button type="button" class="fsb-action-btn fsb-action-delete" @click.stop="removeItem(index)" title="Delete">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Editor panel --}}
                            <div class="fsb-item-editor" x-show="item.isOpen" x-transition.duration.200ms>
                                {{-- Normal field settings --}}
                                <div class="fsb-editor-grid" x-show="!isLayoutField(item)">
                                    <label class="fsb-editor-checkbox" x-show="!isLayoutField(item)">
                                        <input type="checkbox" x-model="item.required" @change="sync()">
                                        <span>Required</span>
                                    </label>

                                    <label>
                                        <span>Label</span>
                                        <input type="text" x-model="item.label" @input="sync()">
                                    </label>

                                    <label x-show="!isParagraph(item)">
                                        <span>Help Text</span>
                                        <input type="text" x-model="item.help_text" @input="sync()">
                                    </label>

                                    <label x-show="!isParagraph(item)">
                                        <span>Placeholder</span>
                                        <input type="text" x-model="item.placeholder" @input="sync()">
                                    </label>

                                    <label>
                                        <span>Class</span>
                                        <input type="text" x-model="item.class" @input="sync()">
                                    </label>

                                    <label>
                                        <span>Name</span>
                                        <input type="text" x-model="item.name" readonly class="is-readonly">
                                    </label>
                                </div>

                                {{-- Header settings --}}
                                <div class="fsb-editor-grid" x-show="isHeader(item)">
                                    <label>
                                        <span>Label</span>
                                        <input type="text" x-model="item.label" @input="sync()">
                                    </label>
                                    <label>
                                        <span>Class</span>
                                        <input type="text" x-model="item.class" @input="sync()">
                                    </label>
                                </div>

                                {{-- Paragraph settings --}}
                                <div class="fsb-editor-grid" x-show="isParagraph(item)">
                                    <label>
                                        <span>Content</span>
                                        <textarea rows="3" x-model="item.content" @input="sync()"></textarea>
                                    </label>
                                    <label>
                                        <span>Class</span>
                                        <input type="text" x-model="item.class" @input="sync()">
                                    </label>
                                </div>

                                {{-- File settings --}}
                                <div class="fsb-editor-grid" x-show="isFileField(item)">
                                    <label class="fsb-editor-checkbox">
                                        <input type="checkbox" x-model="item.multiple_files" @change="sync()">
                                        <span>Allow users to upload multiple files</span>
                                    </label>
                                </div>

                                {{-- Select settings --}}
                                <div class="fsb-editor-grid" x-show="isSelectField(item)">
                                    <label class="fsb-editor-checkbox">
                                        <input type="checkbox" x-model="item.allow_multiple" @change="sync()">
                                        <span>Allow Multiple Selections</span>
                                    </label>
                                </div>

                                {{-- Options editor --}}
                                <div class="fsb-options-section" x-show="isSelectField(item)">
                                    <div class="fsb-options-title">Options</div>
                                    <template x-for="(option, optionIndex) in item.options" :key="item.instance_id + '-opt-' + optionIndex">
                                        <div class="fsb-option-row">
                                            <input class="fsb-option-marker" type="checkbox" :checked="!!item.allow_multiple" disabled>
                                            <input type="text" placeholder="Label" x-model="option.label" @input="sync()">
                                            <input type="text" placeholder="Value" x-model="option.value" @input="sync()">
                                            <button type="button" class="fsb-option-delete" @click.stop="removeOption(index, optionIndex)">&times;</button>
                                        </div>
                                    </template>
                                    <button type="button" class="fsb-option-add" @click.stop="addOption(index)">+ Add option</button>
                                </div>

                                <div class="fsb-editor-close-row">
                                    <button type="button" class="fsb-editor-close" @click.stop="item.isOpen = false">Close</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Preview Modal --}}
        <div class="fsb-modal-overlay" x-show="previewOpen" x-transition.opacity style="display: none;" @click.self="closePreview()">
            <div class="fsb-modal" @click.stop>
                <div class="fsb-modal-header">
                    <h3 class="fsb-modal-title">Form Preview</h3>
                    <p class="fsb-modal-subtitle">This is how candidates will see the form when they click Apply.</p>
                    <button type="button" class="fsb-modal-close-x" @click="closePreview()">&times;</button>
                </div>
                <div class="fsb-modal-body" x-html="renderPreviewHtml()"></div>
                <div class="fsb-modal-footer">
                    <button type="button" class="fsb-modal-close-btn" @click="closePreview()">Close</button>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>

@once
    @push('styles')
    <style>
        /* ── Root ── */
        .form-schema-builder-root { width: 100%; }

        /* ── Toolbar ── */
        .fsb-toolbar { display: flex; justify-content: flex-end; margin-bottom: 8px; }
        .fsb-preview-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 16px; background: #fff; border: 1px solid #d1d5db;
            border-radius: 6px; font-size: .8125rem; color: #374151; cursor: pointer;
        }
        .dark .fsb-preview-btn { background: #1f2937; border-color: #374151; color: #d1d5db; }
        .fsb-preview-btn:hover:not(:disabled) { background: #f9fafb; border-color: #9ca3af; }
        .dark .fsb-preview-btn:hover:not(:disabled) { background: #374151; border-color: #6b7280; }
        .fsb-preview-btn:disabled { opacity: .4; cursor: not-allowed; }

        /* ── Layout ── */
        .fsb-layout { display: flex; gap: 12px; min-height: 500px; }

        /* ── Palette ── */
        .fsb-palette { width: 30%; min-width: 180px; border: 1px solid #e5e7eb; background: #fafbfc; border-radius: 8px; overflow: hidden; align-self: flex-start; }
        .dark .fsb-palette { border-color: #374151; background: #1f2937; }
        .fsb-palette-title { padding: 10px 12px; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #e5e7eb; background: #f3f4f6; }
        .dark .fsb-palette-title { color: #9ca3af; border-color: #374151; background: #111827; }
        .fsb-palette-item { padding: 8px 12px; border-bottom: 1px solid #f3f4f6; cursor: pointer; user-select: none; background: #f9fafb; }
        .dark .fsb-palette-item { border-color: #374151; background: #111827; }
        .fsb-palette-item:hover { background: #eef2ff; }
        .dark .fsb-palette-item:hover { background: #1e3a8a; }
        .fsb-palette-item.is-disabled { opacity: 0.4; cursor: not-allowed; background: #f3f4f6; }
        .dark .fsb-palette-item.is-disabled { background: #1f2937; }
        .fsb-palette-label { font-size: 13px; color: #374151; }
        .dark .fsb-palette-label { color: #d1d5db; }

        /* ── Canvas ── */
        .fsb-canvas { flex: 1; border: 1px solid #e5e7eb; min-height: 500px; background: #fff; border-radius: 8px; }
        .dark .fsb-canvas { border-color: #374151; background: #111827; }
        .fsb-canvas-empty { border: 2px dashed #d1d5db; margin: 16px; min-height: 200px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; color: #9ca3af; font-size: 13px; border-radius: 8px; }
        .dark .fsb-canvas-empty { border-color: #4b5563; color: #6b7280; }
        .fsb-canvas-list { padding: 8px; min-height: 120px; }

        /* ── Item ── */
        .fsb-item { border: 1px solid #e5e7eb; margin-bottom: 6px; background: #fff; border-radius: 6px; overflow: hidden; }
        .dark .fsb-item { border-color: #374151; background: #1f2937; }
        .fsb-item-header {
            display: flex; align-items: center; padding: 8px 10px; gap: 8px;
            cursor: default; user-select: none;
        }
        .fsb-item-header.is-open { border-bottom: 1px solid #e5e7eb; background: #f0f4ff; }
        .dark .fsb-item-header.is-open { border-color: #374151; background: #1e3a8a; }

        /* Drag handle (LEFT) */
        .fsb-drag-handle {
            border: none; background: transparent; color: #9ca3af; cursor: grab;
            padding: 2px; display: flex; align-items: center; flex-shrink: 0;
        }
        .fsb-drag-handle:active { cursor: grabbing; }

        /* Label (CENTER, flex-grow) */
        .fsb-item-label { flex: 1; font-size: 13px; color: #111827; font-weight: 500; }
        .dark .fsb-item-label { color: #f3f4f6; }

        /* Actions (RIGHT) — buttons always clickable; only opacity toggles on hover */
        .fsb-item-actions { display: flex; gap: 2px; opacity: 0; transition: opacity .15s ease; flex-shrink: 0; margin-left: auto; }
        .fsb-item:hover .fsb-item-actions,
        .fsb-item-header.is-open .fsb-item-actions { opacity: 1; }

        .fsb-action-btn {
            border: none; background: transparent; color: #6b7280;
            width: 28px; height: 28px; border-radius: 4px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            position: relative; z-index: 10; pointer-events: auto;
        }
        .fsb-action-edit:hover { background: #eef2ff; color: #4f46e5; }
        .fsb-action-delete:hover { background: #fef2f2; color: #dc2626; }

        /* ── Editor ── */
        .fsb-item-editor { background: #f8fafc; padding: 12px; border-top: 1px dashed #c7d2fe; }
        .dark .fsb-item-editor { background: #111827; border-color: #4338ca; }
        .fsb-editor-grid { display: grid; gap: 10px; margin-bottom: 10px; }
        .fsb-editor-grid label { display: flex; flex-direction: column; gap: 4px; }
        .fsb-editor-grid label > span { font-size: 11px; color: #374151; font-weight: 500; }
        .dark .fsb-editor-grid label > span { color: #d1d5db; }
        .fsb-editor-grid input, .fsb-editor-grid textarea {
            border: 1px solid #d1d5db; border-radius: 4px; padding: 6px 8px;
            font-size: 12px; background: #fff; color: #111827;
        }
        .dark .fsb-editor-grid input, .dark .fsb-editor-grid textarea { border-color: #4b5563; background: #1f2937; color: #f3f4f6; }
        .fsb-editor-grid input:focus, .fsb-editor-grid textarea:focus { outline: 2px solid #818cf8; outline-offset: -1px; }
        .fsb-editor-grid input.is-readonly { background: #f3f4f6; color: #6b7280; cursor: not-allowed; }
        .dark .fsb-editor-grid input.is-readonly { background: #374151; color: #9ca3af; }
        .fsb-editor-checkbox { display: flex !important; flex-direction: row !important; align-items: center; gap: 6px; }

        .fsb-options-section { margin-top: 6px; }
        .fsb-options-title { font-size: 11px; color: #374151; font-weight: 500; margin-bottom: 6px; }
        .fsb-option-row { display: grid; grid-template-columns: 20px 1fr 1fr 26px; gap: 6px; margin-bottom: 6px; align-items: center; }
        .fsb-option-row input[type='text'] { border: 1px solid #d1d5db; border-radius: 4px; padding: 6px 8px; font-size: 12px; }
        .fsb-option-marker { width: 12px; height: 12px; }
        .fsb-option-delete { border: none; background: #ef4444; color: #fff; border-radius: 4px; width: 22px; height: 22px; cursor: pointer; font-size: 14px; display:flex; align-items:center; justify-content:center; }
        .fsb-option-add { border: 1px dashed #d1d5db; background: #fff; border-radius: 4px; padding: 6px 10px; font-size: 12px; cursor: pointer; color: #6b7280; }
        .fsb-option-add:hover { border-color: #818cf8; color: #4f46e5; }

        .fsb-editor-close-row { text-align: right; margin-top: 8px; }
        .fsb-editor-close { border: 1px solid #d1d5db; background: #fff; color: #6b7280; cursor: pointer; font-size: 12px; padding: 4px 14px; border-radius: 4px; }
        .fsb-editor-close:hover { background: #f3f4f6; }

        /* ── Modal ── */
        .fsb-modal-overlay {
            position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 9999;
            display: flex; align-items: center; justify-content: center; padding: 24px;
        }
        .fsb-modal {
            background: #fff; border-radius: 12px; width: 100%; max-width: 700px; max-height: 85vh;
            display: flex; flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2); overflow: hidden;
        }
        .dark .fsb-modal { background: #1f2937; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6); }
        .fsb-modal-header { padding: 20px 24px 16px; border-bottom: 1px solid #e5e7eb; position: relative; }
        .dark .fsb-modal-header { border-color: #374151; }
        .fsb-modal-title { font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0 0 4px; }
        .dark .fsb-modal-title { color: #f3f4f6; }
        .fsb-modal-subtitle { font-size: .8125rem; color: #9ca3af; margin: 0; }
        .dark .fsb-modal-subtitle { color: #6b7280; }
        .fsb-modal-close-x {
            position: absolute; top: 12px; right: 16px; border: none; background: transparent;
            font-size: 22px; color: #9ca3af; cursor: pointer;
        }
        .fsb-modal-body { flex: 1; overflow-y: auto; padding: 24px; }
        .fsb-modal-footer { padding: 14px 24px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; }
        .fsb-modal-close-btn {
            padding: 7px 20px; background: #f3f4f6; border: 1px solid #d1d5db;
            border-radius: 6px; font-size: .875rem; color: #374151; cursor: pointer;
        }
        .fsb-modal-close-btn:hover { background: #e5e7eb; }

        /* ── Preview field styling ── */
        .fsb-modal-body .preview-field { margin-bottom: 16px; }
        .fsb-modal-body .preview-field label { display: block; font-size: 13px; font-weight: 600; color: #111827; margin-bottom: 4px; }
        .fsb-modal-body .preview-field .preview-help { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
    </style>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            if (window.__fsbRegistered) return;
            window.__fsbRegistered = true;

            Alpine.data('formSchemaBuilder', ({ initialSchema, availableFields, skillOptions, statePath }) => ({
                statePath,
                availableFields,
                skillOptions,
                items: [],
                previewOpen: false,
                _sortableInstance: null,

                init() {
                    this.items = this.normalizeSchema(initialSchema || []);
                    this.sync();
                    this.$nextTick(() => this.initSortable());
                },

                initSortable() {
                    const self = this;
                    const initInstance = () => {
                        if (!window.Sortable || !self.$refs.canvasList) return;
                        if (self._sortableInstance) {
                            self._sortableInstance.destroy();
                            self._sortableInstance = null;
                        }

                        self._sortableInstance = window.Sortable.create(self.$refs.canvasList, {
                            animation: 150,
                            handle: '.js-drag-handle',
                            filter: '.fsb-action-btn, .fsb-option-delete, .fsb-option-add, .fsb-editor-close',
                            preventOnFilter: false,
                            forceFallback: false,
                            ghostClass: 'sortable-ghost',
                            onStart() {
                                // Close all editors while dragging
                                self.items.forEach(i => i.isOpen = false);
                            },
                            onEnd(evt) {
                                // SortableJS has already moved the DOM node.
                                // Splice the data array to match the new order.
                                const movedItem = self.items.splice(evt.oldIndex, 1)[0];
                                self.items.splice(evt.newIndex, 0, movedItem);

                                // Force Alpine to re-render by reassigning the array.
                                // This ensures Alpine rebuilds every DOM node from scratch,
                                // restoring all event bindings.
                                self.items = [...self.items];

                                self.$nextTick(() => {
                                    // Re-initialize SortableJS after Alpine re-renders
                                    // because Alpine destroyed and rebuilt the DOM nodes.
                                    if (self._sortableInstance) {
                                        self._sortableInstance.destroy();
                                        self._sortableInstance = null;
                                    }
                                    self.initSortable();
                                    self.sync();
                                });
                            },
                        });
                    };

                    if (window.Sortable) { initInstance(); return; }

                    const scriptId = 'sortablejs-cdn';
                    if (!document.getElementById(scriptId)) {
                        const s = document.createElement('script');
                        s.id = scriptId;
                        s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js';
                        s.async = true;
                        s.onload = () => initInstance();
                        document.head.appendChild(s);
                    } else {
                        const wait = setInterval(() => {
                            if (window.Sortable) { clearInterval(wait); initInstance(); }
                        }, 100);
                    }
                },

                normalizeSchema(schema) {
                    if (!Array.isArray(schema)) return [];
                    return schema.map(raw => this.normalizeItem(raw)).filter(Boolean);
                },

                normalizeItem(rawItem) {
                    const data = rawItem?.data && typeof rawItem.data === 'object' ? rawItem.data : rawItem || {};
                    const rawKey = data.field_key || rawItem?.type || data.type;
                    const field = this.getField(rawKey);
                    if (!field) return null;

                    return {
                        instance_id: this.generateId(),
                        field_key: field.field_key,
                        type: data.type || field.type,
                        db_column: data.db_column ?? field.db_column ?? null,
                        name: data.name ?? field.name ?? null,
                        label: data.label ?? field.label,
                        content: data.content ?? data.label ?? field.label,
                        required: !!data.required,
                        help_text: data.help_text ?? '',
                        placeholder: data.placeholder ?? '',
                        class: data.class ?? 'form-control',
                        allow_multiple: data.allow_multiple ?? !!field.allow_multiple,
                        multiple_files: !!data.multiple_files,
                        options: this.defaultOptionsFor(field, data.options),
                        isOpen: false,
                    };
                },

                getField(key) {
                    return this.availableFields.find(f => f.field_key === key) || null;
                },

                defaultOptionsFor(field, current = null) {
                    if (Array.isArray(current) && current.length > 0) {
                        return current.map(o => ({ label: o?.label ?? '', value: o?.value ?? '' }));
                    }
                    if (field.type === 'skill_select') {
                        return (this.skillOptions || []).map(o => ({ label: o?.label ?? '', value: o?.value ?? '' }));
                    }
                    return (field.default_options || []).map(o => ({ label: o?.label ?? '', value: o?.value ?? '' }));
                },

                canAddField(field) {
                    return !!field.repeatable || !this.items.some(i => i.field_key === field.field_key);
                },

                startPaletteDrag(event, field) {
                    if (!this.canAddField(field)) { event.preventDefault(); return; }
                    event.dataTransfer.effectAllowed = 'copy';
                    event.dataTransfer.setData('text/plain', field.field_key);
                },

                handleCanvasDrop(event) {
                    const key = event.dataTransfer.getData('text/plain');
                    if (key) this.addField(key);
                },

                addField(fieldKey) {
                    const field = this.getField(fieldKey);
                    if (!field || !this.canAddField(field)) return;

                    this.items.push({
                        instance_id: this.generateId(),
                        field_key: field.field_key,
                        type: field.type,
                        db_column: field.db_column ?? null,
                        name: field.name ?? null,
                        label: field.label,
                        content: field.label,
                        required: false,
                        help_text: '',
                        placeholder: '',
                        class: 'form-control',
                        allow_multiple: !!field.allow_multiple,
                        multiple_files: false,
                        options: this.defaultOptionsFor(field),
                        isOpen: false,
                    });
                    this.sync();
                },

                removeItem(index) {
                    if (index < 0 || index >= this.items.length) return;
                    this.items.splice(index, 1);
                    this.sync();
                },

                toggleEdit(index) {
                    if (index < 0 || index >= this.items.length) return;
                    const wasOpen = this.items[index].isOpen;
                    // Close all others (accordion)
                    this.items.forEach(i => { i.isOpen = false; });
                    // Toggle the clicked one
                    this.items[index].isOpen = !wasOpen;
                },

                addOption(index) {
                    const item = this.items[index] ?? null;
                    if (item) { item.options.push({ label: '', value: '' }); this.sync(); }
                },

                removeOption(index, optIdx) {
                    const item = this.items[index] ?? null;
                    if (item) { item.options.splice(optIdx, 1); this.sync(); }
                },

                isHeader(item) { return item.field_key === 'header'; },
                isParagraph(item) { return item.field_key === 'paragraph'; },
                isLayoutField(item) { return this.isHeader(item) || this.isParagraph(item); },
                isFileField(item) { return item.type === 'file'; },
                isSelectField(item) { return item.type === 'select' || item.type === 'skill_select'; },

                rowLabel(item) {
                    if (this.isParagraph(item)) return item.content || item.label || 'Paragraph';
                    return item.label || this.getField(item.field_key)?.label || 'Field';
                },

                schemaForSave() {
                    return this.items.map(item => {
                        const data = {
                            field_key: item.field_key, type: item.type, db_column: item.db_column,
                            name: item.name, label: item.label, class: item.class,
                        };
                        if (this.isParagraph(item)) data.content = item.content;
                        if (!this.isLayoutField(item)) {
                            data.required = !!item.required;
                            data.help_text = item.help_text || '';
                            data.placeholder = item.placeholder || '';
                        }
                        if (this.isFileField(item)) data.multiple_files = !!item.multiple_files;
                        if (this.isSelectField(item)) {
                            data.allow_multiple = !!item.allow_multiple;
                            data.options = (item.options || []).map(o => ({ label: o?.label ?? '', value: o?.value ?? '' }));
                        }
                        return { type: item.field_key, data };
                    });
                },

                sync() {
                    const payload = this.schemaForSave();
                    if (this.$refs.hiddenInput) this.$refs.hiddenInput.value = JSON.stringify(payload);
                    // wire:ignore on the parent div prevents Livewire from morphing
                    // this DOM subtree, so Alpine bindings survive. But we still need
                    // $wire.set() to push data to Livewire server state for Filament save.
                    if (this.$wire && this.statePath) this.$wire.set(this.statePath, payload);
                },

                openPreview() { if (this.items.length > 0) this.previewOpen = true; },
                closePreview() { this.previewOpen = false; },

                renderPreviewHtml() {
                    if (this.items.length === 0) return '<p style="color:#9ca3af;">No fields configured.</p>';

                    const s = 'width:100%;border:1px solid #d1d5db;border-radius:6px;padding:8px 10px;font-size:13px;background:#fff;color:#374151;';

                    return this.items.map(item => {
                        if (this.isHeader(item))
                            return '<h2 style="font-size:22px;font-weight:700;margin:16px 0 8px;color:#111827;border-bottom:1px solid #e5e7eb;padding-bottom:8px;">' + this.esc(item.label || 'Header') + '</h2>';

                        if (this.isParagraph(item))
                            return '<p style="font-size:14px;line-height:1.65;color:#4b5563;margin:0 0 14px;">' + this.esc(item.content || '') + '</p>';

                        const req = item.required ? '<span style="color:#ef4444;"> *</span>' : '';
                        const lbl = '<label style="display:block;font-size:13px;font-weight:600;color:#111827;margin-bottom:4px;">' + this.esc(item.label || '') + req + '</label>';
                        const hlp = item.help_text ? '<p style="font-size:12px;color:#6b7280;margin:0 0 4px;">' + this.esc(item.help_text) + '</p>' : '';

                        if (item.type === 'file')
                            return '<div style="margin-bottom:16px;">' + lbl + hlp + '<input type="file" style="' + s + '"' + (item.multiple_files ? ' multiple' : '') + '></div>';

                        if (item.type === 'select' || item.type === 'skill_select') {
                            const opts = (item.options || []).map(o => '<option value="' + this.esc(o.value) + '">' + this.esc(o.label) + '</option>').join('');
                            if (item.allow_multiple) {
                                const pills = (item.options || []).slice(0, 3).map(o =>
                                    '<span style="background:#eef2ff;border:1px solid #c7d2fe;padding:3px 10px;border-radius:16px;font-size:12px;color:#4338ca;font-weight:500;">' + this.esc(o.label) + '</span>'
                                ).join('');
                                return '<div style="margin-bottom:16px;">' + lbl + hlp +
                                    '<div style="' + s + 'display:flex;gap:6px;flex-wrap:wrap;align-items:center;min-height:38px;cursor:pointer;">' +
                                    pills + '<span style="color:#9ca3af;font-size:12px;">Select options...</span></div></div>';
                            }
                            return '<div style="margin-bottom:16px;">' + lbl + hlp + '<select style="' + s + '">' + opts + '</select></div>';
                        }

                        if (item.type === 'date')
                            return '<div style="margin-bottom:16px;">' + lbl + hlp + '<input type="date" style="' + s + '"></div>';

                        if (item.type === 'number')
                            return '<div style="margin-bottom:16px;">' + lbl + hlp + '<input type="number" style="' + s + '" placeholder="' + this.esc(item.placeholder || '') + '"></div>';

                        const textareaKeys = ['introduce_yourself','interests','resident','current_accommodation','reason_for_leaving_job','job_description','birthplace','home_town'];
                        if (textareaKeys.includes(item.field_key))
                            return '<div style="margin-bottom:16px;">' + lbl + hlp + '<textarea rows="3" style="' + s + 'resize:vertical;min-height:80px;" placeholder="' + this.esc(item.placeholder || '') + '"></textarea></div>';

                        const t = item.field_key === 'email' ? 'email' : 'text';
                        return '<div style="margin-bottom:16px;">' + lbl + hlp + '<input type="' + t + '" style="' + s + '" placeholder="' + this.esc(item.placeholder || '') + '"></div>';
                    }).join('');
                },

                esc(v) {
                    const d = document.createElement('div');
                    d.textContent = String(v ?? '');
                    return d.innerHTML;
                },

                generateId() {
                    if (window.crypto?.randomUUID) {
                        return window.crypto.randomUUID();
                    }

                    return Date.now() + '-' + Math.random().toString(16).slice(2, 12);
                },
            }));
        });
    </script>
    @endpush
@endonce
