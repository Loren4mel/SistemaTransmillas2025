<?php
require("../login_autentica.php"); 
//require_once("expdf/lib/pdf/mpdf.php");  
$id_usuario=$_SESSION['usuario_id'];
$id_nombre=$_SESSION['usuario_nombre'];
$nivel_acceso=$_SESSION['usuario_rol'];
$id_sedes=$_SESSION['usu_idsede'];
require "../ticket/fpdf/fpdf.php";
$DB = new DB_mssql;
$DB->conectar();
$DB1 = new DB_mssql;
$DB1->conectar();
$DB2 = new DB_mssql;
$DB2->conectar();
$DB3 = new DB_mssql;
$DB3->conectar();
@$param5=$_REQUEST["param5"];
@$codigoguia=$_REQUEST["codigoguia"];
@$modulo=$_REQUEST["modulo"];
    //set it to writable location, a place for temp generated PNG files
    $PNG_TEMP_DIR = dirname(__FILE__).DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR;
    
    //html PNG location prefix
    $PNG_WEB_DIR = 'temp/';

	include "qrlib.php";    
	  //ofcourse we need rights to create temp dir
	  if (!file_exists($PNG_TEMP_DIR))
	  mkdir($PNG_TEMP_DIR);

	function textoPdf($texto) {
		return utf8_decode($texto);
	}

	function colorearQr($archivo, $rojo, $verde, $azul) {
		if (!function_exists('imagecreatefrompng')) {
			return;
		}

		$imagen = imagecreatefrompng($archivo);
		if (!$imagen) {
			return;
		}

		$colorMarca = imagecolorallocate($imagen, $rojo, $verde, $azul);
		$ancho = imagesx($imagen);
		$alto = imagesy($imagen);

		for ($x = 0; $x < $ancho; $x++) {
			for ($y = 0; $y < $alto; $y++) {
				$rgb = imagecolorat($imagen, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;

				if ($r < 80 && $g < 80 && $b < 80) {
					imagesetpixel($imagen, $x, $y, $colorMarca);
				}
			}
		}

		imagepng($imagen, $archivo);
		imagedestroy($imagen);
	}
  
  
  $filename = $PNG_TEMP_DIR.'test.png'; 
  

if($param5==""){ $param5=$id_sedes;}
if($param34!=''){ $fechaactual=$param34;  }




if($param5!=''){ 
	$id_sedes=$param5; 
	$idcidades=ciudadesedes($param5,$DB);
	if($idcidades=='0'){
		$conde1=" and cli_idciudad in (0) "; 

	}else {
	  $conde1=" and cli_idciudad in $idcidades "; 	
	}
} else {  

$idcidades=ciudadesedes($id_sedes,$DB);
if($idcidades=='0'){
	$conde1="";

}else {
  $conde1=" and cli_idciudad in $idcidades "; 	
}


}
if($nivel_acceso==1){ $conde2="";  	 } else {  $conde2=" and idsedes=$id_sedes"; }


$conde3="";
// $conde5="and (ser_fechaguia like '$fechaactual%' or ser_fechaasignacion like '$fechaactual%' )";
$conde5="and ser_fechaasignacion like '$fechaactual%' ";
if($param4==''){ $param4=0;   } else { $conde3=" and inner_sedes=$param4";   }

$pdf = new FPDF($orientation='L',$unit='mm', array(120,70));
//$pdf = new FPDF();
$errorCorrectionLevel = 'L';
$Level = 'L';
$matrixPointSize = 6;
$matrixPointSize2= 80;
//$data = "josemirandacastaneda";


//Fields Name position

if($param33!=''){ $conde4 ="and ((ser_idresponsable='$param33' and ser_fechaasignacion like '$fechaactual%') or (ser_idusuarioguia='$param33' and ser_fechaguia like '$fechaactual%' )) "; $conde5="";  } 
//if($param33!=''){ $conde4 ="and (ser_idresponsable='$param33' and ser_fechaasignacion like '$fechaactual%') "; $conde5="";  } 
if($modulo==2){

	 $sql="SELECT `idservicios`,ser_guiare,ser_consecutivo,ciu_nombre,`ser_direccioncontacto`,ser_piezas,`ser_telefonocontacto`,`ser_destinatario`
	FROM serviciosdia   where  ser_estado!='100' and idservicios=$codigoguia ORDER BY ser_fechafinal $asc ";
	
} elseif($modulo==1){ 

	$sql="SELECT `idservicios`,ser_guiare,ser_consecutivo,ciu_nombre,`ser_direccioncontacto`,ser_piezas,`ser_telefonocontacto`,`ser_destinatario`
	FROM serviciosdia   where  ser_estado>='6' and ser_estado!='100'  $conde1 $conde3 $conde4  $conde5 ORDER BY ser_fechafinal $asc ";
	
} elseif($modulo==3){ 
	$conde4 ="and (ser_idresponsable='$param33' and ser_fechaasignacion like '$param34%') ";
	$sql="SELECT `idservicios`,ser_guiare,ser_consecutivo,ciu_nombre,`ser_direccioncontacto`,ser_piezas,`ser_telefonocontacto`,`ser_destinatario`
	FROM serviciosdia   where ser_estado>=3 and ser_estado<=11  and ser_estado!='100' and ser_estado!='5' $conde1 $conde3 $conde4  $conde5 ORDER BY ser_fechafinal $asc ";
	if ($id_usuario==523) {
		echo$sql;
		echo"<script>console.log('$sql')</script>";
	}
}elseif($modulo==4){ 
	$conde4 ="and (ser_idresponsable='$param33' and ser_fechafinal like '$param34%') ";
	$sql="SELECT `idservicios`,ser_guiare,ser_consecutivo,ciu_nombre,`ser_direccioncontacto`,ser_piezas,`ser_telefonocontacto`,`ser_destinatario`
	FROM serviciosdia   where ser_idverificadopeso=0  and ser_estado=6  $conde1 $conde3 $conde4  $conde5 ORDER BY ser_fechafinal $asc ";
	
}elseif ($modulo==5) {
	$conde1 ="and (ser_idresponsable='$param33' and ser_fechafinal like '$param34%') ";
	
	$idcidades=ciudadesedes($param36,$DB);
	if($idcidades=='0'){
		$conde1="";

	}else {
	$conde2=" and cli_idciudad in $idcidades "; 	
	}
	$conde5 = 
	$sql="SELECT 
	`idservicios`,
	ser_guiare,
	ser_consecutivo,
	ciu_nombre,
	`ser_direccioncontacto`,
	ser_piezas,
	`ser_telefonocontacto`,
	`ser_destinatario` 
	FROM serviciosdia 
	inner join usuarios on idusuarios=ser_idresponsable 
	where ser_idverificadopeso=0 
	$conde1
	and ser_estado in (6,4) 
    $conde2
	ORDER BY idservicios,ser_fechafinal asc;";
}


$DB->Execute($sql);
$va=0; 
//echo "doss";
  while($rw1=mysqli_fetch_row($DB->Consulta_ID))
   {
	$sql2="SELECT `gui_recogio` FROM `guias` WHERE `gui_idservicio`='$rw1[0]'";
	$DB1->Execute($sql2);
	$rw2=mysqli_fetch_row($DB1->Consulta_ID);
	$operario ="Recogio: $rw2[0]"; 

	for($b=1;$b<=$rw1[5];$b++){
		$pdf->AddPage();
		$pdf->SetAutoPageBreak(false);
			$rw1[4]=str_replace("&"," ", $rw1[4]);

			$code = $rw1[2]." ".$b;
			$urlserver=$_SERVER['HTTP_HOST'];
			$data="http://$urlserver/validaenviadaqr.php?guia=".$rw1[2]."&pieza=$b";
			$filename = $PNG_TEMP_DIR.'test'.md5($data.'|'.$errorCorrectionLevel.'|'.$matrixPointSize).'.png';
			QRcode::png($data, $filename, $errorCorrectionLevel, $matrixPointSize, 2);   
			colorearQr($filename, 17, 48, 29);

			$ciudadEtiqueta=substr(strtoupper($rw1[3]), 0, 16);
			$guiaEtiqueta=strtoupper($rw1[2]);

			$pdf->SetFillColor(255, 255, 255);
			$pdf->Rect(1, 1, 118, 68, 'F');

			$pdf->SetFillColor(17, 48, 29);
			$pdf->Rect(81, 1, 38, 68, 'F');
			$pdf->Rect(86, 1, 33, 68, 'F');

			$pdf->SetFillColor(255, 255, 255);
			$pdf->Rect(84, 14, 32, 43, 'F');

			$pdf->SetTextColor(17, 48, 29);
			$pdf->SetDrawColor(17, 48, 29);
			$pdf->SetLineWidth(0.8);

			$pdf->SetFont('Arial','B',25);
			$pdf->SetXY(5, 6);
			$pdf->Cell(65, 12, "TRANSMILLAS", 0, 0, 'L');

			$pdf->Line(5, 22, 24, 22);
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('Arial','B',8);
			$pdf->SetXY(29, 18);
			$pdf->Cell(50, 6, textoPdf($operario), 0, 0, 'L');

			$pdf->SetTextColor(17, 48, 29);
			$pdf->SetFont('Arial','B',9);
			$pdf->SetXY(5, 27);
			$pdf->Cell(74, 6, textoPdf("$ciudadEtiqueta | $guiaEtiqueta | $b PIEZA"), 0, 0, 'L');
			$pdf->Line(58, 31, 77, 31);

			$pdf->Line(5, 55, 56, 55);
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('Arial','B',10);
			$pdf->SetXY(5, 56);
			$pdf->Cell(60, 7, textoPdf("Recibo a satisfacción"), 0, 0, 'L');

			$pdf->Image($PNG_WEB_DIR.basename($filename),86,17,28,0,'PNG');
			unlink($filename);

			//$pdf->Cell(100,5, $pdf->Image('../galerias/'.$row['portada'], $pdf->GetX()+40, $pdf->GetY()+3, 30), 1,0,'C');
		//	$pdf->Ln();
		}
   }
//$pdf->AutoPrint();
$pdf->output(); 



?>
