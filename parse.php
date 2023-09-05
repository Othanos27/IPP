<?php
ini_set('display_errors', 'stderr');

$dom = new DOMDocument('1.0','UTF-8');
$dom->formatOutput = true;

$order = 1;

//$defined_variables = array();

/**
 * Print user manual
 */
function printHelp() {
    echo("parse.php <input\n");
    echo("\t- runs the script\n");
    echo("parse.php --help\n");
    echo("\t- prints this help\n");
}

/**
 * Get type of argument and argument
 * 
 * @param string $symbol argument of instruction
 * @return array (type, argument)
 */
function getSymb($symbol) {
    if (str_contains($symbol, "@")) {
        $symbol_token = explode("@", $symbol);
        switch ($symbol_token[0]) {
            case "GF":
            case "LF":
            case "TF":
                return array("var", $symbol);
            case "int":
                return array("int", $symbol_token[1]);
            case "bool":
                return array("bool", $symbol_token[1]);
            case "string":
                return array("string", $symbol_token[1]); 
            case "nil":
                return array("nil", $symbol_token[1]);
            default:
                error_log("Lexical or syntax error.");
                exit(23);
        }
    }
    else {
        switch($symbol) {
            case "int":
            case "bool":
            case "string":
            case "nil":
                return "type";
            default:
                if (preg_match("/[a-zA-Z_\-$&%*!?][a-zA-Z_\-$&%*!?0-9]*/", $symbol)) {
                    return array("label", $symbol);
                }
                else {
                    error_log("Lexical or syntax error.");
                    exit(23);
                }
        }
    }    
}

/**
 * Make XML for instruction without arguments
 * 
 * @param string $line_token_0 instruction in uppercase
 */
function xmlInstructionMake0args($line_token_0) {
    global $dom, $xml_program, $order;
    $xml_instruction = $dom->createElement('instruction');
    $xml_program->appendChild($xml_instruction);
    $xml_instruction->setAttribute('order', $order++);
    $xml_instruction->setAttribute('opcode', $line_token_0);
}

/**
 * Make XML for instruction with 1 argument
 * 
 * @param string $line_token_0 instruction in uppercase
 * @param string $line_token_1 first argument of instruction
 */
function xmlInstructionMake1arg($line_token_0, $line_token_1) {
    global $dom, $xml_program, $order;
    $xml_instruction = $dom->createElement('instruction');
    $xml_program->appendChild($xml_instruction);
    $xml_instruction->setAttribute('order', $order++);
    $xml_instruction->setAttribute('opcode', $line_token_0);

    $symb = getSymb($line_token_1);
    $arg1 = $dom->createElement('arg1', $symb[1]);
    $arg1->setAttribute('type', $symb[0]);
    $xml_instruction->appendChild($arg1);
}

/**
 * Make XML for instruction with 2 arguments
 * 
 * @param string $line_token_0 instruction in uppercase
 * @param string $line_token_1 first argument of instruction
 * @param string $line_token_2 second argument of instruction
 */
function xmlInstructionMake2args($line_token_0, $line_token_1, $line_token_2) {
    global $dom, $xml_program, $order;
    $xml_instruction = $dom->createElement('instruction');
    $xml_program->appendChild($xml_instruction);
    $xml_instruction->setAttribute('order', $order++);
    $xml_instruction->setAttribute('opcode', $line_token_0);

    $symb1 = getSymb($line_token_1);
    $symb2 = getSymb($line_token_2);
    $arg1 = $dom->createElement('arg1', $symb1[1]);
    $arg1->setAttribute('type', $symb1[0]);
    $arg2 = $dom->createElement('arg2', $symb2[1]);
    $arg2->setAttribute('type', $symb2[0]);
    $xml_instruction->appendChild($arg1);
    $xml_instruction->appendChild($arg2);
    
}

/**
 * Make XML for instruction with 3 arguments
 * 
 * @param string $line_token_0 instruction in uppercase
 * @param string $line_token_1 first argument of instruction
 * @param string $line_token_2 second argument of instruction
 * @param string $line_token_3 third argument of instruction
 */
function xmlInstructionMake3args($line_token_0, $line_token_1, $line_token_2, $line_token_3) {
    global $dom, $xml_program, $order;
    $xml_instruction = $dom->createElement('instruction');
    $xml_program->appendChild($xml_instruction);
    $xml_instruction->setAttribute('order', $order++);
    $xml_instruction->setAttribute('opcode', $line_token_0);

    $symb1 = getSymb($line_token_1);
    $symb2 = getSymb($line_token_2);
    $symb3 = getSymb($line_token_3);
    $arg1 = $dom->createElement('arg1', $symb1[1]);
    $arg1->setAttribute('type', $symb1[0]);
    $arg2 = $dom->createElement('arg2', $symb2[1]);
    $arg2->setAttribute('type', $symb2[0]);
    $arg3 = $dom->createElement('arg3', $symb3[1]);
    $arg3->setAttribute('type', $symb3[0]);
    $xml_instruction->appendChild($arg1);
    $xml_instruction->appendChild($arg2);
    $xml_instruction->appendChild($arg3);
}

/**
 * Check if argument is variable
 * 
 * @param string $to_match argument to be checked
 * @return bool true if argument is variable 
 */
function isVar ($to_match) {
    return preg_match("/(GF|LF|TF)@[a-zA-Z_\-$&%*!?][a-zA-Z_\-$&%*!?0-9]*/", $to_match);
}

/**
 * Check if argument is constant
 * 
 * @param string $to_match argument to be checked
 * @return bool true if argument is constant 
 */
function isConst ($to_match) {
    if (str_contains($to_match, "@")) {
        $tmp = explode("@", $to_match, 2);
        switch($tmp[0]) {
            case "int":
                return preg_match("/(\-\d+$)|(\d+$)/", $tmp[1]);
            case "bool":
                return preg_match("/^true$|^false$/", $tmp[1]);
            case "string":
                if (substr($to_match, -1) == "@") {
                    return true;
                }                
                return preg_match("/[^\s\#]*/", $tmp[1]);                
            case "nil":
                return preg_match("/^nil$/", $tmp[1]);
            default:
                return false;
        }
    }
    else {
        return false;
    }
}

/**
 * Check if argument is label
 * 
 * @param string $to_match argument to be checked
 * @return bool true if argument is label
 */
function isLabel ($to_match) {
    return preg_match("/[a-zA-Z_\-$&%*!?][a-zA-Z_\-$&%*!?0-9]*/", $to_match);
}

function fcMove($line_token_0, $line_token_1, $line_token_2) {
    if (isVar($line_token_1)) {
        if (isVar($line_token_2) || isConst($line_token_2)) {
            xmlInstructionMake2args($line_token_0, $line_token_1, $line_token_2);
            return;
        }        
    }
    error_log("Lexical or syntax error. (MOVE)");
    exit(23);
}

function fcDefVar($line_token_0, $line_token_1) {
    /*global $defined_variables;
    for($i = 0; $i < count($defined_variables); $i++) {
        if (strcmp($line_token_1, $defined_variables[$i]) == 0){
            error_log("Redefinition of the variable is not allowed.");
            exit(52);
        } 
    }
    array_push($defined_variables, $line_token_1);*/
    if (isVar($line_token_1)) {
        xmlInstructionMake1arg($line_token_0, $line_token_1);
    }
    else {
        error_log("Lexical or syntax error. (DEFVAR)");
        exit(23);
    }
}

function fcCall($line_token_0, $line_token_1) {
    if (isLabel($line_token_1)) {
        xmlInstructionMake1arg($line_token_0, $line_token_1);
    }
    else {
        error_log("Lexical or syntax error. (CALL)");
        exit(23);
    }
}

function fcPushs($line_token_0, $line_token_1) {
    if (isVar($line_token_1) || isConst($line_token_1)) {
        xmlInstructionMake1arg($line_token_0, $line_token_1);
    }
    else {
        error_log("Lexical or syntax error. (PUSHS)");
        exit(23);
    }
}

function fcPops($line_token_0, $line_token_1) {
    if (isVar($line_token_1)) {
        xmlInstructionMake1arg($line_token_0, $line_token_1);
    }
    else {
        error_log("Lexical or syntax error. (POPS)");
        exit(23);
    }
}

function fcAddSubMulIdiv($line_token_0, $line_token_1, $line_token_2, $line_token_3) {
    if (isVar($line_token_1)) {
        if (isConst($line_token_2) && isConst($line_token_3)) {
            if (str_contains($line_token_2, "int") && str_contains($line_token_3, "int")) {
                xmlInstructionMake3args($line_token_0, $line_token_1, $line_token_2, $line_token_3);
                return;
            }
        }
    }
    error_log("Lexical or syntax error. (ARITHMETICAL)");
    exit(23);    
}

function fcLtGtEq($line_token_0, $line_token_1, $line_token_2, $line_token_3) {
    if (isVar($line_token_1)) {
        if (isConst($line_token_2) && isConst($line_token_3)) {
            if (!str_contains($line_token_2, "nil") && !str_contains($line_token_3, "nil")) {
                if ( (str_contains($line_token_2, "int") && str_contains($line_token_3, "int")) || (str_contains($line_token_2, "string") && str_contains($line_token_3, "string")) || (str_contains($line_token_2, "bool") && str_contains($line_token_3, "bool")) ) {
                    xmlInstructionMake3args($line_token_0, $line_token_1, $line_token_2, $line_token_3);
                    return;
                }
            }
            else {
                if ($line_token_0 == "EQ") {
                    xmlInstructionMake3args($line_token_0, $line_token_1, $line_token_2, $line_token_3);
                    return;
                }                
            }
        }
    }
    error_log("Lexical or syntax error. (RELATIONAL)");
    exit(23); 
}

function fcAndOr($line_token_0, $line_token_1, $line_token_2, $line_token_3) {
    if (isVar($line_token_1)) {
        if (isConst($line_token_2) && isConst($line_token_3)) {
            if (str_contains($line_token_2, "bool") && str_contains($line_token_3, "bool")) {
                xmlInstructionMake3args($line_token_0, $line_token_1, $line_token_2, $line_token_3);
                return;
            }
        }
    }
    error_log("Lexical or syntax error. (LOGICAL)");
    exit(23);  
}

function fcNot($line_token_0, $line_token_1, $line_token_2) {
    if (isVar($line_token_1)) {
        if (isConst($line_token_2)) {
            if (str_contains($line_token_2, "bool")) {
                xmlInstructionMake2args($line_token_0, $line_token_1, $line_token_2);
                return;
            }
        }
    }
    error_log("Lexical or syntax error. (LOGICAL)");
    exit(23); 
}

function fcInt2Char($line_token_0, $line_token_1, $line_token_2) {
    if (isVar($line_token_1)) {
        if (isConst($line_token_2)) {
            if (str_contains($line_token_2, "int")) {
                xmlInstructionMake2args($line_token_0, $line_token_1, $line_token_2);
                return;
            }
        }
    }
    error_log("Lexical or syntax error. (INT2CHAR)");
    exit(23); 
}

function fcStr2Int($line_token_0, $line_token_1, $line_token_2, $line_token_3) {
    if (isVar($line_token_1)) {
        if (isConst($line_token_2) && isConst($line_token_3)) {
            if (str_contains($line_token_2, "string") && str_contains($line_token_3, "int")) {
                xmlInstructionMake3args($line_token_0, $line_token_1, $line_token_2, $line_token_3);
                return;
            }
        }
    }
    error_log("Lexical or syntax error. (STRI2INT)");
    exit(23); 
}

function fcRead($line_token_0, $line_token_1, $line_token_2) {
    if (isVar($line_token_1)) {
        if ($line_token_2 == "int" || $line_token_2 == "string" || $line_token_2 == "bool") {
            xmlInstructionMake2args($line_token_0, $line_token_1, $line_token_2);
            return;
        }
    }
    error_log("Lexical or syntax error. (READ)");
    exit(23); 
}

function fcWrite($line_token_0, $line_token_1) {
    if (isVar($line_token_1) || isConst($line_token_1)) {
        xmlInstructionMake1arg($line_token_0, $line_token_1);
    }
    else {
        error_log("Lexical or syntax error. (WRITE)");
        exit(23); 
    }
}

function fcConcat($line_token_0, $line_token_1, $line_token_2, $line_token_3) {
    if (isVar($line_token_1)) {
        if ( (isConst($line_token_3) || isVar($line_token_3)) && (isConst($line_token_2) || isVar($line_token_2)) ) {
            if ( (isConst($line_token_2) && str_contains($line_token_2, "string")) || (isConst($line_token_3) && str_contains($line_token_3, "string")) ) {
                xmlInstructionMake3args($line_token_0, $line_token_1, $line_token_2, $line_token_3);
                return;
            }
        }
    }
    error_log("Lexical or syntax error. (CONCAT)");
    exit(23); 
}

function fcStrLen($line_token_0, $line_token_1, $line_token_2) {
    if (isVar($line_token_1)) {
        if (isConst($line_token_2)) {
            if (str_contains($line_token_2, "string")) {
                xmlInstructionMake2args($line_token_0, $line_token_1, $line_token_2);
                return;
            }
        }
    }
    error_log("Lexical or syntax error. (STRLEN)");
    exit(23); 
}

function fcGetChar($line_token_0, $line_token_1, $line_token_2, $line_token_3) {
    if (isVar($line_token_1)) {
        if (isConst($line_token_2) && isConst($line_token_3)) {
            if (str_contains($line_token_2, "string") && str_contains($line_token_3, "int")) {
                xmlInstructionMake3args($line_token_0, $line_token_1, $line_token_2, $line_token_3);
                return;
            }
        }
    }
    error_log("Lexical or syntax error. (GETCHAR)");
    exit(23); 
}

function fcSetChar($line_token_0, $line_token_1, $line_token_2, $line_token_3) {
    if (isVar($line_token_1)) {
        if (isConst($line_token_2) && isConst($line_token_3)) {
            if (str_contains($line_token_2, "int") && str_contains($line_token_3, "string")) {
                xmlInstructionMake3args($line_token_0, $line_token_1, $line_token_2, $line_token_3);
                return;
            }
        }
    }
    error_log("Lexical or syntax error. (SETCHAR)");
    exit(23); 
}

function fcType($line_token_0, $line_token_1, $line_token_2) {
    if (isVar($line_token_1)) {
        if (isVar($line_token_2) || isConst($line_token_2)) {
            xmlInstructionMake2args($line_token_0, $line_token_1, $line_token_2);
            return;
        }
    }
    error_log("Lexical or syntax error. (TYPE)");
    exit(23); 
}

function fcLabelJump($line_token_0, $line_token_1) {
    if (isLabel($line_token_1)) {
        xmlInstructionMake1arg($line_token_0, $line_token_1);
    }
    else {
        error_log("Lexical or syntax error. (LABEL/JUMP)");
        exit(23);
    }
}

function fcJumpIf($line_token_0, $line_token_1, $line_token_2, $line_token_3) {
    if (isLabel($line_token_1)) {
        if ( (isConst($line_token_3) || isVar($line_token_3)) && (isConst($line_token_2) || isVar($line_token_2)) ) {
            xmlInstructionMake2args($line_token_0, $line_token_1, $line_token_2);
            return;
        }
    }
    error_log("Lexical or syntax error. (JUMPIF)");
    exit(23);
}

function fcExit($line_token_0, $line_token_1) {
    if (isVar($line_token_1)) {
        xmlInstructionMake1arg($line_token_0, $line_token_1);
    }
    else if (isConst($line_token_1) && str_contains($line_token_1, "int")) {
        xmlInstructionMake1arg($line_token_0, $line_token_1);
    }
    else {
        error_log("Lexical or syntax error. (EXIT)");
        exit(23);
    }
}

function fcDPrint($line_token_0, $line_token_1) {
    if (isVar($line_token_1) || isConst($line_token_1)) {
        xmlInstructionMake1arg($line_token_0, $line_token_1);
    }
    else {
        error_log("Lexical or syntax error. (DPRINT)");
        exit(23);
    }
}

/* Zpracovani argumentu */
if ($argc > 2) {
    error_log("Too many parameters!");
    exit(10);
}
else if ($argc == 2) {
    if ($argv[1] == "--help" || $argv[1] == "-h") {
        printHelp();
        exit(0);
    }
    else {
        error_log("Incorrect parameter. Try --help.");
        exit(10);
    }
}

/* hlavni while loop celeho porgramu */
$header = false;
while ($line = fgets(STDIN)) {
    /* osetreni hlavicky souboru */
    if (!$header) {
        if ($line == ".IPPcode22\n") {
            $header = true;
            $xml_program = $dom->createElement('program');
            $dom->appendChild($xml_program);
            $xml_program->setAttribute('language', 'IPPcode22');
            continue;
        }
        else {
            error_log("File header is incorrect or missing.");
            exit(21);
        }
    }

    /* rozdeleni radku na jednotlive "tokeny", odstraneni EOL a osetreni zacatku komentare bez mezery */
    $pos = strpos($line, "#");
    if ($pos != 0) {
        $line = substr_replace($line, " ", $pos, 0);
        $line = substr_replace($line, " ", $pos + 2, 0)."\n";
    }    
    $line_token = explode(" ", trim($line, "\n"));

    switch (strtoupper($line_token[0])) {
        /* ramce, volani funkci */
        case "MOVE":
            fcMove(strtoupper($line_token[0]), $line_token[1], $line_token[2]);
            break;
        case "CREATEFRAME":
        case "PUSHFRAME":
        case "POPFRAME":
        case "BREAK":
            if ($line_token[1] == "") {
                xmlInstructionMake0args($line_token[0]);
                break;
            }
            else {
                error_log("Lexical or syntax error. (ARGUMENTS)");
                exit(23);
            }            
        case "DEFVAR":
            fcDefVar(strtoupper($line_token[0]), $line_token[1]);
            break;
        case "CALL":
            fcCall(strtoupper($line_token[0]), $line_token[1]);
            break;
        case "RETURN":
            xmlInstructionMake0args(strtoupper($line_token[0]));
            break;
        /* datovy zasobnik */
        case "PUSHS":
            fcPushs(strtoupper($line_token[0]), $line_token[1]);
            break;
        case "POPS":
            fcPops(strtoupper($line_token[0]), $line_token[1]);
            break;
        /* aritmeticke, relacni, booleovske a konverzni instrukce */
        case "ADD":
        case "SUB":
        case "MUL":
        case "IDIV":
            fcAddSubMulIdiv(strtoupper($line_token[0]), $line_token[1], $line_token[2], $line_token[3]);
            break;
        case "LT":
        case "GT":
        case "EQ":
            fcLtGtEq(strtoupper($line_token[0]), $line_token[1], $line_token[2], $line_token[3]);
            break;
        case "AND":
        case "OR":
            fcAndOr(strtoupper($line_token[0]), $line_token[1], $line_token[2], $line_token[3]);
            break;
        case "NOT":
            fcNot(strtoupper($line_token[0]), $line_token[1], $line_token[2]);
            break;
        case "INT2CHAR":
            fcInt2Char(strtoupper($line_token[0]), $line_token[1], $line_token[2]);
            break;
        case "STRI2INT":
            fcStr2Int(strtoupper($line_token[0]), $line_token[1], $line_token[2], $line_token[3]);
            break;
        /* vstupne-vystupni instrukce */
        case "READ":
            fcRead(strtoupper($line_token[0]), $line_token[1], $line_token[2]);
            break;
        case "WRITE":
            fcWrite(strtoupper($line_token[0]), $line_token[1]);
            break;
        /* prace s retezci */
        case "CONCAT":
            fcConcat(strtoupper($line_token[0]), $line_token[1], $line_token[2], $line_token[3]);
            break;
        case "STRLEN":
            fcStrLen(strtoupper($line_token[0]), $line_token[1], $line_token[2]);
            break;
        case "GETCHAR":
            fcGetChar(strtoupper($line_token[0]), $line_token[1], $line_token[2], $line_token[3]);
            break;
        case "SETCHAR":
            fcSetChar(strtoupper($line_token[0]), $line_token[1], $line_token[2], $line_token[3]);
            break;
        /* prace s typy */
        case "TYPE":
            fcType(strtoupper($line_token[0]), $line_token[1], $line_token[2]);
            break;
        /* instrukce pro rizeni toku programu */
        case "LABEL":
        case "JUMP":
            fcLabelJump(strtoupper($line_token[0]), $line_token[1]);
            break;
        case "JUMPIFEQ":
        case "JUMPIFNEQ":
            fcJumpIf(strtoupper($line_token[0]), $line_token[1], $line_token[2], $line_token[3]);
            break;
        case "EXIT":
            fcExit(strtoupper($line_token[0]), $line_token[1]);
            break;
        /* ladici instrukce */
        case "DPRINT":
            fcDPrint(strtoupper($line_token[0]), $line_token[1]);
            break;
        case "#":
            break;
        default:
            if (preg_match("/#.*/", $line_token[0])) {
                break;
            }
            else {
                error_log("Unknown or incorrect opcode.");
                exit(22);
            }
    }
}
echo $dom->saveXML();
?>