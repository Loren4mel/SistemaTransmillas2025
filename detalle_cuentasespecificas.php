<?php 
require("login_autentica.php");
include("cabezote3.php"); 

$asc="ASC";
$conde=" ";
$conde2=" ";
$conde3=" ";
$conde4=" ";
$conde6=" ";
$conde7=" ";
$actualizar = $_GET['accion'];



if($param34!=''){ $fechaactual=$param34." 00:00:00";  }
if($param36!=''){ $fechainicial=$param36." 23:59:59";  }
if($param33!=''){ $conde="and (`cue_idoperador`= '$param33' or  `cue_idoperentrega`= '$param33' )";  }
if($param35!=''){ $id_sedes=$param35; 

	$idcidades=ciudadesedes($id_sedes,$DB);
	if($idcidades=='0'){
		$conde2="";

	}else {
	  $conde2=" and (cue_idciudadori in $idcidades )"; 	
	}	
}

if($param43!=0 or $param45!=0){ 
	if($param43==1){ $conde7=" and ser_numerofactura<>'' "; }else if ($param43==2){ $conde7=" and ser_numerofactura IS NULL ";}
	if($param45==1){ $conde7.=" and ser_valor<='19000' "; }else if($param45==2){$conde7.=" and ser_valor>='19000' ";}
	
	$conde6 =" and (ser_estado='10' or ser_clasificacion='1') and ser_clasificacion!='2'";
}

if($param44!=''){
	$conde7.=" and ser_transporta='$param44' ";
}


if ($param38 != '') {
    // Separar los IDs que llegan en la cadena
    $ids = explode(',', $param38);

    $ciudadesdes = [];

    foreach ($ids as $id) {
        $id = trim($id); // limpiar espacios
        $resultado = ciudadesedes($id, $DB);

        if ($resultado != '0') {
            // Quitar los paréntesis que devuelve la función
            $resultado = str_replace(['(', ')'], '', $resultado);
            $ciudadesdes[] = $resultado; 
        }
    }

    if (count($ciudadesdes) > 0) {
        // Unir todos los resultados y encerrar en un solo par de paréntesis
        $lista = implode(',', $ciudadesdes);
        $conde4 = " AND ser_ciudadentrega IN ($lista)";
    } else {
        $conde4 = "";
    }
}
if($param42=='NoRecibe'){ $conde5=" and (ser_llego !='SI' and ser_idverificadopeso !='1') or ser_estado='7'";   }else if($param42=='SiRecibe'){ $conde5=" and (ser_llego ='SI' )";   }
	
echo "<table class='table table-hover' ><tr bgcolor='#074F91' class='tittle3'><td colspan='0' width='0' align='center'>Trabajador <br>Todo<input type='checkbox' id='check_todos' onclick='seleccionarTodos()' ></td>";	
$FB->titulo_azul1("Fecha Recogida ",1,0,0); 
$FB->titulo_azul1("Fecha Entrega",1,0,0); 
$FB->titulo_azul1("#Guia",1,0,0); 
$FB->titulo_azul1("Pre-guia",1,0,0); 
if ($param43==1) {
	$FB->titulo_azul1("Factura",1,0,0);
}

$FB->titulo_azul1("Contado+%",1,0,0); 
$FB->titulo_azul1("Credito+%",1,0,0); 
$FB->titulo_azul1("AL Cobro+%",1,0,0); 
$FB->titulo_azul1("Prestamos",1,0,0); 
$FB->titulo_azul1("%Pre.",1,0,0); 
$FB->titulo_azul1("Seguro",1,0,0); 
$FB->titulo_azul1("%Seguro",1,0,0); 
$FB->titulo_azul1("Abonos",1,0,0); 

$FB->titulo_azul1("Total Guia",1,0,0); 

$FB->titulo_azul1("Servicio",1,0,0); 
$FB->titulo_azul1("Destino",1,0,0); 
$FB->titulo_azul1("Validado Por",1,0,0); 
$FB->titulo_azul1("Manifiesto-Codigo",1,0,0); 

$FB->titulo_azul1("Datos",1,0,0); 


$conde1=""; 
$conde3="and (cue_idoperador>0 )"; 

if($param32!="" and $param31!=""){ 
 $conde1="and $param31 like '%$param32%' "; 
  }else { $conde1="  "; } 


$sql="SELECT `idservicios`,cue_fecharecogida,`cue_fecha`,ser_consecutivo,ser_guiare,cue_tipoevento, `cue_valorflete`, `cue_prestamo`,`cue_porprestamo`,`cue_vrdeclarado`, `cue_pordeclarado`,  `cue_abono`,cue_tipopago,cue_validar,cue_usuvalido,ciu_nombre,ser_manifiesto,ser_peso,ser_telefonocontacto,ser_numerofactura
 FROM servicios inner join cuentaspromotor on cue_idservicio=idservicios inner join ciudades on ser_ciudadentrega=idciudades  where date(cue_fecharecogida)>='$fechaactual' and  date(cue_fecharecogida)<='$fechainicial' $conde $conde1 $conde2   $conde3 $conde4 $conde5 $conde6 $conde7 ORDER BY ser_guiare  $asc ";

$DB->Execute($sql); 

$va=0; 
$contsinpesar=0;
$totalcontado=0;
$totalcredito=0;
$totalalcobro=0;
$totalguias=0;
$totalprestamos=0;
$totalporp=0;
$totalseguro=0;
$totalpors=0;
$totalabonos=0;
$ids=array();
if($param40=='' && $param41=''){
	$manifiesto='';
}else{
	$manifiesto=$param40."-".$param41;
}

	while($rw1=mysqli_fetch_row($DB->Consulta_ID))
	{

		
		$id_p=$rw1[0];
		$va++; $p=$va%2;
		$telefonoDes=$rw1[18];
		$sql1="SELECT cli_idciudad,cli_telefono FROM `serviciosdia` WHERE idservicios='$id_p'";
		$DB1->Execute($sql1);
		$rw2=mysqli_fetch_row($DB1->Consulta_ID);
		
		$telefonoRemi=$rw2[1];
		$consecutivo=$rw1[3];
		if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
		echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
		//$direc1=str_replace("&"," ", $rw1[4]);
		//$direct2=str_replace("&"," ", $rw1[7]);

		echo"<td align='center' ><input type='checkbox'  onchange='selecionado1($id_p,\"$telefonoDes\",\"$consecutivo\",\"$telefonoRemi\")' class='check_hijo' id='".$id_p."s1' value='$id_p'></td>";

		echo "<td>".$rw1[1]."</td>
		<td>".$rw1[2]."</td>
		<td>".$rw1[3]."</td>
		<td>".$rw1[4]."</td>
		";
		if ($param43==1) {
			echo "<td>".$rw1[19]."</td>";
		}

		if($rw1[5]==1){  //contado
		$contado=$rw1[6]+$rw1[10];
		echo "<td>$ ".$contado."</td>
		<td>$ 0</td>
		<td>$ 0</td>
		";	
		
		$totalcontado=$contado+$totalcontado;
			
		}else if($rw1[5]==2){  //credito
		$credito=$rw1[6]+$rw1[10];
		echo "<td>$ 0</td>
		<td>$ ".$credito."</td>
		<td>$ 0</td>
		";
		$totalcredito=$credito+$totalcredito;
		
		}else if($rw1[5]==3){  //al cobro
		$alcobro=$rw1[6]+$rw1[10];
		echo "<td>$ 0</td>
		<td>$ 0</td>
		<td>$".$alcobro."</td>
		";
		$totalalcobro=$alcobro+$totalalcobro;
		
		}else {
			echo "<td>$ 0</td>
		<td>$ 0</td>
		<td>$ 0</td>
		";
		}
		echo "
		<td>$ ".$rw1[7]."</td>
		<td>$ ".$rw1[8]."</td>
		<td>$ ".$rw1[9]."</td>
		<td>$ ".$rw1[10]."</td>
		<td>$ ".$rw1[11]."</td>
		";



		$totalprestamos=$rw1[7]+$totalprestamos;
		$totalporp=$rw1[8]+$totalporp;
		$totalseguro=$rw1[9]+$totalseguro;
		$totalpors=$rw1[10]+$totalpors;
		$totalabonos=$rw1[11]+$totalabonos;
		
		
		$totales=$rw1[6]+$rw1[7]+$rw1[8];
		// if ($totales<=2000) {
		// 	$colorflete="#e73c3c";
		// 	$contsinpesar=$contsinpesar+1;
		// }else {
		// 	$colorflete="";
		// }
		$texto="";
		if ($rw1[17]<1 or ($totales<=1000 and $rw1[17]>=1 ) ) {
			$colorflete="#e73c3c";
			$contsinpesar=$contsinpesar+1;

		}else {
			$colorflete="";
		}
		
		if($rw2[0]==0 or $rw2[0]==""){
			$colorflete="#e73c3c";
			$texto.="Falta la ciudad de origen";
			$contsinpesar=$contsinpesar+1;
		}



		echo "<td bgcolor='$colorflete' title='$texto'>$".$totales."</td><td> ".$rw1[12]."</td>
		";
		$totalguias=$totales+$totalguias;
		echo "<td> ".$rw1[15]."</td><td> ".$rw1[14]."</td>
		";

		if($actualizar =='actualizar'){
			echo "<td> ".$manifiesto."</td>";
		}else{
			echo "<td> ".$rw1[16]."</td>";
		}

		echo "<td align='center' ><a  onclick='pop_dis5($id_p,\"Recogidas\")';  style='cursor: pointer;' title='Recogidas' ><img src='img/recogidas.png'></a></td>";
		
		echo "</tr>"; 
		

		array_push($ids, $id_p);
	}
	

		if($actualizar =='actualizar' && $manifiesto!=''){

			
			$idsconsulta=implode(',', $ids);
		 	$sqlupdate="UPDATE  servicios SET ser_manifiesto='$manifiesto' where idservicios in ($idsconsulta)";
			$DB->Execute($sqlupdate); 
		}
		if ($contsinpesar>0) {
			echo '<div class="alert alert-danger" role="alert">
			¡Hay '.$contsinpesar.' guias sin pesar verifique!
		  </div>';
			// echo 'alertaGuias();';
			// echo '</script>';
		}
		
	$FB->titulo_azul1(" Totales :",1,0,10); 
	$FB->titulo_azul1(" Datos:$va",1,0,0); 
	$FB->titulo_azul1(" ",1,0,0); 
	$FB->titulo_azul1(" ",1,0,0); 
	if ($param43==1) {
		$FB->titulo_azul1(" ",1,0,0);
	}
	$FB->titulo_azul1("$ $totalcontado",1,0,0); 
	$FB->titulo_azul1("$ $totalcredito",1,0,0); 
	$FB->titulo_azul1("$ $totalalcobro",1,0,0); 
	$FB->titulo_azul1("$ $totalprestamos",1,0,0); 
	$FB->titulo_azul1("$ $totalporp",1,0,0); 
	$FB->titulo_azul1("$ $totalseguro",1,0,0); 
	$FB->titulo_azul1("$ $totalpors",1,0,0); 
	$FB->titulo_azul1("$ $totalabonos",1,0,0); 
	$FB->titulo_azul1("$ $totalguias",1,0,0); 
	$FB->titulo_azul1("$ $totalguias",1,0,0); 
	//echo "<tr><td align='center' > </td>"; echo "</tr>"; 

include("footer.php");
?>
