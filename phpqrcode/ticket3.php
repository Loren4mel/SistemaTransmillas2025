<?php
require("../login_autentica.php"); 
//require_once("expdf/lib/pdf/mpdf.php");  
$id_usuario=$_SESSION['usuario_id'];
$id_nombre=$_SESSION['usuario_nombre'];
$nivel_acceso=$_SESSION['usuario_rol'];
$id_sedes=$_SESSION['usu_idsede'];
require "../ticket/fpdf/fpdf.php";
class StickerPDF extends FPDF {
	function RoundedRect($x, $y, $w, $h, $r, $style = '') {
		$k = $this->k;
		$hp = $this->h;
		if ($style == 'F') {
			$op = 'f';
		} elseif ($style == 'FD' || $style == 'DF') {
			$op = 'B';
		} else {
			$op = 'S';
		}
		$myArc = 4 / 3 * (sqrt(2) - 1);
		$this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
		$xc = $x + $w - $r;
		$yc = $y + $r;
		$this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
		$this->_Arc($xc + $r * $myArc, $yc - $r, $xc + $r, $yc - $r * $myArc, $xc + $r, $yc);
		$xc = $x + $w - $r;
		$yc = $y + $h - $r;
		$this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
		$this->_Arc($xc + $r, $yc + $r * $myArc, $xc + $r * $myArc, $yc + $r, $xc, $yc + $r);
		$xc = $x + $r;
		$yc = $y + $h - $r;
		$this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
		$this->_Arc($xc - $r * $myArc, $yc + $r, $xc - $r, $yc + $r * $myArc, $xc - $r, $yc);
		$xc = $x + $r;
		$yc = $y + $r;
		$this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
		$this->_Arc($xc - $r, $yc - $r * $myArc, $xc - $r * $myArc, $yc - $r, $xc, $yc - $r);
		$this->_out($op);
	}

	function Polygon($points, $style = 'D') {
		if (count($points) < 2) {
			return;
		}
		$k = $this->k;
		$hp = $this->h;
		$op = ($style == 'F') ? 'f' : (($style == 'FD' || $style == 'DF') ? 'B' : 'S');
		$this->_out(sprintf('%.2F %.2F m', $points[0][0] * $k, ($hp - $points[0][1]) * $k));
		for ($i = 1; $i < count($points); $i++) {
			$this->_out(sprintf('%.2F %.2F l', $points[$i][0] * $k, ($hp - $points[$i][1]) * $k));
		}
		$this->_out('h ' . $op);
	}

	function Ellipse($x, $y, $rx, $ry, $style = 'D') {
		$op = ($style == 'F') ? 'f' : (($style == 'FD' || $style == 'DF') ? 'B' : 'S');
		$lx = 4 / 3 * (sqrt(2) - 1) * $rx;
		$ly = 4 / 3 * (sqrt(2) - 1) * $ry;
		$k = $this->k;
		$h = $this->h;
		$this->_out(sprintf('%.2F %.2F m', ($x + $rx) * $k, ($h - $y) * $k));
		$this->_Arc($x + $rx, $y - $ly, $x + $lx, $y - $ry, $x, $y - $ry);
		$this->_Arc($x - $lx, $y - $ry, $x - $rx, $y - $ly, $x - $rx, $y);
		$this->_Arc($x - $rx, $y + $ly, $x - $lx, $y + $ry, $x, $y + $ry);
		$this->_Arc($x + $lx, $y + $ry, $x + $rx, $y + $ly, $x + $rx, $y);
		$this->_out($op);
	}

	function IconLocation($x, $y) {
		$this->SetFillColor(0, 0, 0);
		$this->Ellipse($x + 2.6, $y + 2.6, 2.5, 2.5, 'F');
		$this->Polygon(array(array($x + 0.6, $y + 4), array($x + 4.6, $y + 4), array($x + 2.6, $y + 8)), 'F');
		$this->SetFillColor(255, 255, 255);
		$this->Ellipse($x + 2.6, $y + 2.6, 0.8, 0.8, 'F');
	}

	function IconBarcode($x, $y) {
		$this->SetDrawColor(0, 0, 0);
		$this->SetLineWidth(0.5);
		$this->RoundedRect($x, $y, 5.8, 5.8, 0.4, 'D');
		for ($i = 0; $i < 5; $i++) {
			$xx = $x + 1 + ($i * 0.9);
			$this->Line($xx, $y + 0.8, $xx, $y + 5);
		}
	}

	function IconBox($x, $y) {
		$this->SetFillColor(0, 0, 0);
		$this->Polygon(array(array($x + 2.8, $y), array($x + 5.6, $y + 1.5), array($x + 2.8, $y + 3), array($x, $y + 1.5)), 'F');
		$this->Polygon(array(array($x, $y + 1.8), array($x + 2.5, $y + 3.2), array($x + 2.5, $y + 6.4), array($x, $y + 5)), 'F');
		$this->Polygon(array(array($x + 3.1, $y + 3.2), array($x + 5.6, $y + 1.8), array($x + 5.6, $y + 5), array($x + 3.1, $y + 6.4)), 'F');
	}

	function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
		$h = $this->h;
		$this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1 * $this->k, ($h - $y1) * $this->k, $x2 * $this->k, ($h - $y2) * $this->k, $x3 * $this->k, ($h - $y3) * $this->k));
	}
}
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

$pdf = new StickerPDF($orientation='L',$unit='mm', array(120,70));
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

			$ciudadEtiqueta=substr(strtoupper($rw1[3]), 0, 14);
			$guiaEtiqueta=strtoupper($rw1[2]);
			$operarioEtiqueta=substr($rw2[0], 0, 34);

			$colorOscuro = array(24, 26, 27);
			$pdf->SetFillColor(255, 255, 255);
			$pdf->Rect(0, 0, 120, 70, 'F');

			$pdf->SetFillColor(255, 255, 255);
			$pdf->SetDrawColor($colorOscuro[0], $colorOscuro[1], $colorOscuro[2]);
			$pdf->SetLineWidth(0.8);
			$pdf->RoundedRect(1.2, 1.2, 117.6, 67.6, 3.5, 'D');

			$pdf->SetFillColor(255, 255, 255);
			$pdf->RoundedRect(82.5, 7, 34, 34, 2, 'D');

			$pdf->SetTextColor($colorOscuro[0], $colorOscuro[1], $colorOscuro[2]);
			$pdf->SetDrawColor($colorOscuro[0], $colorOscuro[1], $colorOscuro[2]);
			$pdf->SetLineWidth(0.7);

			$pdf->SetFont('Arial','B',25);
			$pdf->SetXY(5, 6);
			$pdf->Cell(65, 12, "TRANSMILLAS", 0, 0, 'L');

			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('Arial','B',8);
			$pdf->SetXY(5, 19.5);
			$pdf->Cell(15, 5, textoPdf("Recogió:"), 0, 0, 'L');
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(20, 19.5);
			$pdf->Cell(58, 5, textoPdf($operarioEtiqueta), 0, 0, 'L');

			$pdf->SetTextColor($colorOscuro[0], $colorOscuro[1], $colorOscuro[2]);
			$pdf->IconLocation(6, 26.5);
			$pdf->SetFont('Arial','B',12);
			$pdf->SetXY(16, 26.5);
			$pdf->Cell(60, 8, textoPdf($ciudadEtiqueta), 0, 0, 'L');

			$pdf->IconBarcode(6, 35);
			$pdf->SetFont('Arial','B',12);
			$pdf->SetXY(16, 34.5);
			$pdf->Cell(60, 8, textoPdf($guiaEtiqueta), 0, 0, 'L');

			$pdf->IconBox(6, 43.5);
			$pdf->SetFont('Arial','B',12);
			$pdf->SetXY(16, 42.8);
			$pdf->Cell(60, 8, textoPdf("$b PIEZA"), 0, 0, 'L');

			$pdf->SetLineWidth(0.8);
			$pdf->Line(5, 49.5, 115, 49.5);

			$pdf->SetFont('Arial','',6.5);
			$pdf->SetXY(82.5, 42);
			$pdf->Cell(34, 4, textoPdf("Escanear para seguimiento"), 0, 0, 'C');
			$pdf->Line(6, 62, 82, 62);
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('Arial','B',8);
			$pdf->SetXY(6, 62.7);
			$pdf->Cell(60, 4, textoPdf("Recibo a satisfacción"), 0, 0, 'L');

			$pdf->Image($PNG_WEB_DIR.basename($filename),85,9.5,29,0,'PNG');
			unlink($filename);

			//$pdf->Cell(100,5, $pdf->Image('../galerias/'.$row['portada'], $pdf->GetX()+40, $pdf->GetY()+3, 30), 1,0,'C');
		//	$pdf->Ln();
		}
   }
//$pdf->AutoPrint();
$pdf->output(); 



?>
