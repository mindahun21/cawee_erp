<x-filament-panels::page>
    {{-- Header Widgets: Cash Position + Pending Approvals --}}
    @if ($this->getHeaderWidgets())
        <x-filament-widgets::widgets
            :columns="$this->getHeaderWidgetsColumns()"
            :data="$this->getWidgetData()"
            :widgets="$this->getHeaderWidgets()"
        />
    @endif

    {{-- Footer Widgets: Charts + Tables --}}
    @if ($this->getFooterWidgets())
        <x-filament-widgets::widgets
            :columns="$this->getFooterWidgetsColumns()"
            :data="$this->getWidgetData()"
            :widgets="$this->getFooterWidgets()"
        />
    @endif
</x-filament-panels::page>

