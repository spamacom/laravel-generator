@php
    echo "<?php".PHP_EOL;
@endphp

namespace {{ $config->namespaces->dataTables }};

use {{ $config->namespaces->model }}\{{ $config->modelNames->name }};
@if($config->options->localized)
use Yajra\DataTables\Html\Column;
@endif
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use PDF;

class {{ $config->modelNames->name }}DataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);
        return $dataTable->addColumn('action', '{{ $config->modelNames->snakePlural }}.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\{{ $config->modelNames->name }} $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query({{ $config->modelNames->name }} $model)
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction(['width' => '80px', 'printable' => false ,'responsivePriority'=>'100'])
            ->parameters(array_merge(
                config('datatables-buttons.parameters'), [
                    'language' => json_decode(
                        file_get_contents(base_path('resources/lang/'.app()->getLocale().'/datatable.json')
                        ),true)
                ]
            ));
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            {!! $columns !!}
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return '{{ $config->modelNames->snakePlural }}_datatable_' . time();
    }
    
    /**
     * Export PDF using DOMPDF
     * @return mixed
     */
    public function pdf()
    {
        $data = $this->getDataForPrint();
        $pdf = PDF::loadView($this->printPreview, compact('data'));
        return $pdf->download($this->filename().'.pdf');
    }
}
