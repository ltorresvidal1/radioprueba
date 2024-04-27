<?php

namespace App\Http\Controllers\datatable;

use App\Models\pacs\series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DatatableController extends Controller
{



  public function estudiosportranscribir(Request $request)
  {

    $query1 = series::where('study.conaudio', '=', '0')
      ->whereNotIn('study.study_iuid', function ($query) {
        $query->select('lecturas.study_id')->from('lecturas');
      })
      ->whereIn('ris_hl7recibidos.puesto_atencion', $request->sede)
      ->whereIn('ris_hl7recibidos.sala', $request->sala)
      ->whereIn('ris_hl7recibidos.prioridad', $request->prioridad)
      ->whereIn('series.modality', $request->modalidad)
      ->join('study', 'study.pk', '=', 'series.study_fk')
      ->join('patient', 'patient.pk', '=', 'study.patient_fk')
      ->join('patient_id', 'patient_id.pk', '=', 'patient.patient_id_fk')
      ->join('person_name', 'person_name.pk', '=', 'patient.pat_name_fk')
      ->join('mwl_item', 'mwl_item.study_iuid', '=', 'study.study_iuid')
      ->join('ris_hl7recibidos', 'ris_hl7recibidos.numero_orden', '=', 'mwl_item.accession_no')
      ->selectRaw("series.modality as modalidad, concat('''',study.study_iuid,'''') as study_pk,patient.pk ,patient_id.pat_id,
      concat( SUBSTRING(study.study_date, 7, 2) ,'/',SUBSTRING(study.study_date, 5, 2) ,'/',SUBSTRING(study.study_date, 0, 5)) as fecha,
      replace (alphabetic_name,'^',' ') as nombre,
      pat_sex as  sexo,case when  ris_hl7recibidos.prioridad is null then 1 else ris_hl7recibidos.prioridad end as prioridad")
      ->distinct();

    $query2 = series::where('study.conaudio', '=', '0')
      ->whereNotIn('study.study_iuid', function ($query) {
        $query->select('lecturas.study_id')->from('lecturas');
      })
      ->whereIn('ris_salas.codigo', $request->sede)
      ->whereRaw('1 IN (' . implode(',', $request->prioridad) . ')')
      ->whereIn('series.modality', $request->modalidad)
      ->join('study', 'study.pk', '=', 'series.study_fk')
      ->join('patient', 'patient.pk', '=', 'study.patient_fk')
      ->join('patient_id', 'patient_id.pk', '=', 'patient.patient_id_fk')
      ->join('person_name', 'person_name.pk', '=', 'patient.pat_name_fk')
      ->join('ris_salas', 'ris_salas.aetitle', '=', 'series.sending_aet')
      ->selectRaw("series.modality as modalidad, concat('''',study.study_iuid,'''') as study_pk,patient.pk ,patient_id.pat_id,
      concat( SUBSTRING(study.study_date, 7, 2) ,'/',SUBSTRING(study.study_date, 5, 2) ,'/',SUBSTRING(study.study_date, 0, 5)) as fecha,
      replace (alphabetic_name,'^',' ') as nombre,
      pat_sex as  sexo, 1 as prioridad")
      ->distinct();


    $estudios = $query1->unionAll($query2)->distinct();

    return datatables()->of($estudios)
      ->addColumn('action', function ($estudios) {
        $acciones = '<div class="text-center">';
        $acciones = '<div class="btn-group">';
        $acciones .= '<a title="Lectura" onclick="RealizarLecturas(' . $estudios->study_pk . ')" class="w-30px h-30px bg-gradient-info rounded-circle d-flex align-items-center justify-content-center"><div class=""><i class="fa fa-edit text-white"></i> </div></a>';
        $acciones .= '</div>';
        $acciones .= '</div>';
        return $acciones;
      })->rawColumns(['action'])->make(true);
  }



  public function estudiosenproceso(Request $request)
  {

    $user = Auth::user();

    $query1 = series::where('conaudio', '>', "0")
      ->where('medico_id', '=', $user->id)
      ->whereNotIn('study.study_iuid', function ($query) {
        $query->select('lecturas.study_id')->from('lecturas');
      })
      ->whereIn('ris_hl7recibidos.puesto_atencion', $request->sede)
      ->whereIn('ris_hl7recibidos.sala', $request->sala)
      ->whereIn('ris_hl7recibidos.prioridad', $request->prioridad)
      ->whereIn('series.modality', $request->modalidad)
      ->join('study', 'study.pk', '=', 'series.study_fk')
      ->join('patient', 'patient.pk', '=', 'study.patient_fk')
      ->join('patient_id', 'patient_id.pk', '=', 'patient.patient_id_fk')
      ->join('person_name', 'person_name.pk', '=', 'patient.pat_name_fk')
      ->join('mwl_item', 'mwl_item.study_iuid', '=', 'study.study_iuid')
      ->join('ris_hl7recibidos', 'ris_hl7recibidos.numero_orden', '=', 'mwl_item.accession_no')
      ->selectRaw("series.modality as modalidad,concat('''',study.study_iuid,'''')  as study_pk,patient.pk ,patient_id.pat_id,
    concat( SUBSTRING(study.study_date, 7, 2) ,'/',SUBSTRING(study.study_date, 5, 2) ,'/',SUBSTRING(study.study_date, 0, 5)) as fecha,
    replace (alphabetic_name,'^',' ')  as nombre,
    pat_sex as  sexo,case when  ris_hl7recibidos.prioridad is null then 0 else ris_hl7recibidos.prioridad end as prioridad")
      ->distinct();

    $query2 = series::where('study.conaudio', '>', '0')
      ->whereNotIn('study.study_iuid', function ($query) {
        $query->select('lecturas.study_id')->from('lecturas');
      })
      ->whereIn('ris_salas.codigo', $request->sede)
      ->whereRaw('1 IN (' . implode(',', $request->prioridad) . ')')
      ->whereIn('series.modality', $request->modalidad)
      ->join('study', 'study.pk', '=', 'series.study_fk')
      ->join('patient', 'patient.pk', '=', 'study.patient_fk')
      ->join('patient_id', 'patient_id.pk', '=', 'patient.patient_id_fk')
      ->join('person_name', 'person_name.pk', '=', 'patient.pat_name_fk')
      ->join('ris_salas', 'ris_salas.aetitle', '=', 'series.sending_aet')
      ->selectRaw("series.modality as modalidad, concat('''',study.study_iuid,'''') as study_pk,patient.pk ,patient_id.pat_id,
      concat( SUBSTRING(study.study_date, 7, 2) ,'/',SUBSTRING(study.study_date, 5, 2) ,'/',SUBSTRING(study.study_date, 0, 5)) as fecha,
      replace (alphabetic_name,'^',' ') as nombre,
      pat_sex as  sexo, 1 as prioridad")
      ->distinct();

    $estudios = $query1->unionAll($query2)->distinct();

    return datatables()->of($estudios)->addColumn('action', function ($estudios) {
      $acciones = '<div class="text-center">';
      $acciones = '<div class="btn-group">';
      $acciones .= '<a title="Lectura" onclick="RealizarLecturas(' . $estudios->study_pk . ')" class="w-30px h-30px bg-gradient-info rounded-circle d-flex align-items-center justify-content-center"><div class=""><i class="fa fa-edit text-white"></i> </div></a>';
      $acciones .= '</div>';
      $acciones .= '</div>';
      return $acciones;
    })->rawColumns(['action'])->make(true);
  }


  public function estudiosporvalidar(Request $request)
  {
    $user = Auth::user();
    $query1 = series::where('lecturas.validado', '=', '0')
      ->where('lecturas.medico_id', '=', $user->id)
      ->whereIn('ris_hl7recibidos.puesto_atencion', $request->sede)
      ->whereIn('ris_hl7recibidos.sala', $request->sala)
      ->whereIn('ris_hl7recibidos.prioridad', $request->prioridad)
      ->whereIn('series.modality', $request->modalidad)
      ->join('study', 'study.pk', '=', 'series.study_fk')
      ->join('patient', 'patient.pk', '=', 'study.patient_fk')
      ->join('patient_id', 'patient_id.pk', '=', 'patient.patient_id_fk')
      ->join('person_name', 'person_name.pk', '=', 'patient.pat_name_fk')
      ->join('lecturas', 'lecturas.study_id', '=', 'study.study_iuid')
      ->join('mwl_item', 'mwl_item.study_iuid', '=', 'study.study_iuid')
      ->join('ris_hl7recibidos', 'ris_hl7recibidos.numero_orden', '=', 'mwl_item.accession_no')
      ->selectRaw("series.modality as modalidad,concat('''',study.study_iuid,'''')  as study_pk,patient.pk ,patient_id.pat_id,
    concat( SUBSTRING(study.study_date, 7, 2) ,'/',SUBSTRING(study.study_date, 5, 2) ,'/',SUBSTRING(study.study_date, 0, 5)) as fecha,
    replace (alphabetic_name,'^',' ')  as nombre,
    pat_sex as  sexo,case when  ris_hl7recibidos.prioridad is null then 0 else ris_hl7recibidos.prioridad end as prioridad")
      ->distinct();

    $query2 = series::where('lecturas.validado', '=', '0')
      ->where('lecturas.medico_id', '=', $user->id)
      ->whereIn('ris_salas.codigo', $request->sede)
      ->whereRaw('1 IN (' . implode(',', $request->prioridad) . ')')
      ->whereIn('series.modality', $request->modalidad)
      ->join('study', 'study.pk', '=', 'series.study_fk')
      ->join('patient', 'patient.pk', '=', 'study.patient_fk')
      ->join('patient_id', 'patient_id.pk', '=', 'patient.patient_id_fk')
      ->join('person_name', 'person_name.pk', '=', 'patient.pat_name_fk')
      ->join('lecturas', 'lecturas.study_id', '=', 'study.study_iuid')
      ->join('ris_salas', 'ris_salas.aetitle', '=', 'series.sending_aet')
      ->selectRaw("series.modality as modalidad,concat('''',study.study_iuid,'''')  as study_pk,patient.pk ,patient_id.pat_id,
    concat( SUBSTRING(study.study_date, 7, 2) ,'/',SUBSTRING(study.study_date, 5, 2) ,'/',SUBSTRING(study.study_date, 0, 5)) as fecha,
    replace (alphabetic_name,'^',' ')  as nombre,
    pat_sex as  sexo,1 as prioridad")
      ->distinct();

    $estudios = $query1->unionAll($query2)->distinct();

    return datatables()->of($estudios)->addColumn('action', function ($estudios) {
      $acciones = '<div class="text-center">';
      $acciones = '<div class="btn-group">';
      $acciones .= '<a title="Lectura" onclick="RealizarLecturas(' . $estudios->study_pk . ')" class="w-30px h-30px bg-gradient-info rounded-circle d-flex align-items-center justify-content-center"><div class=""><i class="fa fa-edit text-white"></i> </div></a>';
      $acciones .= '</div>';
      $acciones .= '</div>';
      return $acciones;
    })->rawColumns(['action'])->make(true);
  }

  public function estudioscompetados(Request $request)
  {

    $urlcompleta = URL::to('/');
    $urlString = (string) $urlcompleta;
    $urlsinpuerto = preg_replace('/:\d+$/', '', $urlString);
    $url = rtrim($urlsinpuerto, '/');


    $user = Auth::user();
    $query1 = series::where('lecturas.validado', '=', '1')
      ->where('lecturas.medico_id', '=', $user->id)
      ->whereRaw("study_date BETWEEN '$request->fechainicial' and '$request->fechafinal'")
      ->whereIn('ris_hl7recibidos.puesto_atencion', $request->sede)
      ->whereIn('ris_hl7recibidos.sala', $request->sala)
      ->whereIn('ris_hl7recibidos.prioridad', $request->prioridad)
      ->whereIn('series.modality', $request->modalidad)
      ->join('study', 'study.pk', '=', 'series.study_fk')
      ->join('patient', 'patient.pk', '=', 'study.patient_fk')
      ->join('patient_id', 'patient_id.pk', '=', 'patient.patient_id_fk')
      ->join('person_name', 'person_name.pk', '=', 'patient.pat_name_fk')
      ->join('lecturas', 'lecturas.study_id', '=', 'study.study_iuid')
      ->join('mwl_item', 'mwl_item.study_iuid', '=', 'study.study_iuid')
      ->join('ris_hl7recibidos', 'ris_hl7recibidos.numero_orden', '=', 'mwl_item.accession_no')
      ->selectRaw("series.modality as modalidad,concat('''',study.study_iuid,'''')  as study_pk,study.study_iuid,patient.pk ,patient_id.pat_id,
      concat( SUBSTRING(study.study_date, 7, 2) ,'/',SUBSTRING(study.study_date, 5, 2) ,'/',SUBSTRING(study.study_date, 0, 5)) as fecha,
      replace (alphabetic_name,'^',' ')  as nombre,
      pat_sex as  sexo,case when  ris_hl7recibidos.prioridad is null then 0 else ris_hl7recibidos.prioridad end as prioridad")
      ->distinct();

    $query2 = series::where('lecturas.validado', '=', '1')
      ->where('lecturas.medico_id', '=', $user->id)
      ->whereRaw("study_date BETWEEN '$request->fechainicial' and '$request->fechafinal'")
      ->whereIn('ris_salas.codigo', $request->sede)
      ->whereRaw('1 IN (' . implode(',', $request->prioridad) . ')')
      ->whereIn('series.modality', $request->modalidad)
      ->join('study', 'study.pk', '=', 'series.study_fk')
      ->join('patient', 'patient.pk', '=', 'study.patient_fk')
      ->join('patient_id', 'patient_id.pk', '=', 'patient.patient_id_fk')
      ->join('person_name', 'person_name.pk', '=', 'patient.pat_name_fk')
      ->join('lecturas', 'lecturas.study_id', '=', 'study.study_iuid')
      ->join('ris_salas', 'ris_salas.aetitle', '=', 'series.sending_aet')
      ->selectRaw("series.modality as modalidad,concat('''',study.study_iuid,'''')  as study_pk,study.study_iuid,patient.pk ,patient_id.pat_id,
      concat( SUBSTRING(study.study_date, 7, 2) ,'/',SUBSTRING(study.study_date, 5, 2) ,'/',SUBSTRING(study.study_date, 0, 5)) as fecha,
      replace (alphabetic_name,'^',' ')  as nombre,
      pat_sex as  sexo,1 as prioridad")
      ->distinct();

    $estudios = $query1->unionAll($query2)->distinct();

    return datatables()->of($estudios)->addColumn('action', function ($estudios) use ($url) {
      $acciones = '<div class="text-center">';
      $acciones = '<div class="btn-group">';
      $acciones .= '<a title="Lectura" onclick="RealizarLecturas(' . $estudios->study_pk . ')" class="w-30px h-30px bg-gradient-info rounded-circle d-flex align-items-center justify-content-center"><div class=""><i class="fa fa-edit text-white"></i> </div></a>';
      $acciones .= '<a title="Imprimir" onclick="ImprimirLecturas(' . $estudios->study_pk . ')" class="w-30px h-30px bg-gradient-warning rounded-circle d-flex align-items-center justify-content-center"><div class=""><i class="fa fa-print text-white"></i> </div></a>';
      $acciones .= '<a href="' . $url . ':3000/viewer?StudyInstanceUIDs=' . $estudios->study_iuid . '" target="_blank"  title="Ver Estudio" class="w-30px h-30px bg-gradient-teal rounded-circle d-flex align-items-center justify-content-center "><div class=""><i class="fa fa-binoculars text-white"></i> </div> </a>';
      $acciones .= '<a title="Decargar Cd" href="descargar_cd" class="w-30px h-30px bg-gradient-purple rounded-circle d-flex align-items-center justify-content-center"><div class=""><i class="fa fa-download text-white"></i> </div></a>';
      $acciones .= '</div>';
      $acciones .= '</div>';
      return $acciones;
    })->rawColumns(['action'])->make(true);
  }




  public function lecturasestudiosclientes(string $idestudio)
  {


    $estudios = series::where('study.study_iuid', '=', "$idestudio")
      ->join('study', 'study.pk', '=', 'series.study_fk')
      ->join('lecturas', 'lecturas.study_id', '=', 'study.study_iuid')
      ->selectRaw("lecturas.id as lecturas_id,lecturas.estudio as estudio,regexp_replace(lecturas.informe, E'<[^>]+>', ' ', 'gi') as informe,lecturas.fechaestudio as fechaestudio,lecturas.informe as informe_html")
      ->distinct()->get();

    return datatables()->of($estudios)->addColumn('action', function ($estudios) {
      $acciones = '<div class="text-center">';
      $acciones = '<div class="btn-group">';
      $acciones .= '<a title="Validar" onclick="ValidarLectura()" class="w-30px h-30px bg-gradient-success rounded-circle d-flex align-items-center justify-content-center"><div class=""><i class="fa fa-check text-white"></i> </div></a>';
      $acciones .= '<a title="Editar" onclick="EditarLectura()" class="w-30px h-30px bg-gradient-info rounded-circle d-flex align-items-center justify-content-center"><div class=""><i class="fa fa-edit text-white"></i> </div></a>';
      $acciones .= '<a title="Imprimir" onclick="ImprimirLectura()" class="w-30px h-30px bg-gradient-warning rounded-circle d-flex align-items-center justify-content-center"><div class=""><i class="fa fa-print text-white"></i> </div></a>';
      $acciones .= '<a title="Eliminar" onclick="EliminarLectura2(\'' . $estudios->lecturas_id . '\')" class="w-30px h-30px bg-gradient-danger rounded-circle d-flex align-items-center justify-content-center"><div class=""><i class="fa fa-trash-alt text-white"></i> </div></a>';
      $acciones .= '</div>';
      $acciones .= '</div>';
      return $acciones;
    })->rawColumns(['action'])->make(true);
  }



  public function estudiospacientes(Request $request)
  {

    $urlcompleta = URL::to('/');
    $urlString = (string) $urlcompleta;
    $urlsinpuerto = preg_replace('/:\d+$/', '', $urlString);
    $url = rtrim($urlsinpuerto, '/');

    $estudios = series::where('lecturas.validado', '=', '1')
      ->where('patient_id.pat_id', '=', $request->paciente)
      ->join('study', 'study.pk', '=', 'series.study_fk')
      ->join('patient', 'patient.pk', '=', 'study.patient_fk')
      ->join('patient_id', 'patient_id.pk', '=', 'patient.patient_id_fk')
      ->join('person_name', 'person_name.pk', '=', 'patient.pat_name_fk')
      ->join('lecturas', 'lecturas.study_id', '=', 'study.study_iuid')
      ->selectRaw("series.modality as modalidad,concat('''',study.study_iuid,'''')  as study_pk,study.study_iuid,patient.pk ,patient_id.pat_id,
      concat( SUBSTRING(study.study_date, 7, 2) ,'/',SUBSTRING(study.study_date, 5, 2) ,'/',SUBSTRING(study.study_date, 0, 5)) as fecha,
      replace (alphabetic_name,'^',' ')  as nombre,lecturas.estudio as estudio,
      pat_sex as  sexo")
      ->distinct();

    return datatables()->of($estudios)->addColumn('action', function ($estudios) use ($url) {
      $acciones = '<div class="text-center">';
      $acciones = '<div class="btn-group">';
      $acciones .= '<a title="Imprimir" onclick="ImprimirLecturaspaciente(' . $estudios->study_pk . ')" class="w-30px h-30px bg-gradient-warning rounded-circle d-flex align-items-center justify-content-center"><div class=""><i class="fa fa-print text-white"></i> </div></a>';
      $acciones .= '<a href="' . $url . ':3000/viewer?StudyInstanceUIDs=' . $estudios->study_iuid . '" target="_blank"  title="Ver Estudio" class="w-30px h-30px bg-gradient-teal rounded-circle d-flex align-items-center justify-content-center "><div class=""><i class="fa fa-binoculars text-white"></i> </div> </a>';
      $acciones .= '</div>';
      $acciones .= '</div>';
      return $acciones;
    })->rawColumns(['action'])->make(true);
  }



  public function estudioscompletadosmedico(Request $request)
  {
    $urlcompleta = URL::to('/');
    $urlString = (string) $urlcompleta;
    $urlsinpuerto = preg_replace('/:\d+$/', '', $urlString);
    $url = rtrim($urlsinpuerto, '/');

    $estudios = series::whereRaw("study_date BETWEEN '$request->fechainicial' and '$request->fechafinal'")
      ->whereIn('ris_salas.codigo', $request->sede)
      ->whereRaw('1 IN (' . implode(',', $request->prioridad) . ')')
      ->whereIn('series.modality', $request->modalidad)
      ->join('study', 'study.pk', '=', 'series.study_fk')
      ->join('patient', 'patient.pk', '=', 'study.patient_fk')
      ->join('patient_id', 'patient_id.pk', '=', 'patient.patient_id_fk')
      ->join('person_name', 'person_name.pk', '=', 'patient.pat_name_fk')
      ->join('ris_salas', 'ris_salas.aetitle', '=', 'series.sending_aet')
      ->leftJoin('lecturas', 'lecturas.study_id', '=', 'study.study_iuid')
      ->selectRaw("series.modality as modalidad,concat('''',study.study_iuid,'''')  as study_pk,study.study_iuid,patient.pk ,patient_id.pat_id,
      concat( SUBSTRING(study.study_date, 7, 2) ,'/',SUBSTRING(study.study_date, 5, 2) ,'/',SUBSTRING(study.study_date, 0, 5)) as fecha,
      replace (alphabetic_name,'^',' ')  as nombre,lecturas.validado as validado,
      pat_sex as  sexo,1 as prioridad")
      ->distinct();

    return datatables()->of($estudios)->addColumn('action', function ($estudios)  use ($url) {
      $acciones = '<div class="text-center">';
      $acciones = '<div class="btn-group">';
      if ($estudios->validado) {
        $acciones .= '<a title="Imprimir" onclick="ImprimirLecturas(' . $estudios->study_pk . ')" class="w-30px h-30px bg-gradient-warning rounded-circle d-flex align-items-center justify-content-center"><div class=""><i class="fa fa-print text-white"></i> </div></a>';
      }
      $acciones .= '<a href="' . $url . ':3000/viewer?StudyInstanceUIDs=' . $estudios->study_iuid . '" target="_blank"  title="Ver Estudio" class="w-30px h-30px bg-gradient-teal rounded-circle d-flex align-items-center justify-content-center "><div class=""><i class="fa fa-binoculars text-white"></i> </div> </a>';
      $acciones .= '</div>';
      $acciones .= '</div>';
      return $acciones;
    })->rawColumns(['action'])->make(true);
  }
}
