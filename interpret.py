"""
argparse -> xml load (xml etree element tree) -> xml check -> xml to instructions -> interpret instructions
"""
from collections import deque
from os import execl
from posixpath import split
import sys
import re
import argparse
import xml.etree.ElementTree as xmlEtree
from xmlrpc.client import Boolean

labelList = []
dataStack = deque()
### TODO ramce

class Variable:
    _variableList = []
    _variableDict = {}

    def __init__(self, name, value) -> None:
        self._name: str = name
        self._value = value.split("@")[1]
        self._type = value.split("@")[0]
        self._variableList.append(self)
        if self._type == "int":
            self._variableDict[name] = int(self._value)
        elif self._type == "string":
            self._variableDict[name] = str(self._value)
        elif self._type == "bool":
            if self._value == "true":
                self._variableDict[name] = True
            else:
                self._variableDict[name] = False
        elif self._type == "nil":
            self._variableDict[name] = None
        else:
            self._variableDict[name] = []

    def getName(self):
        return self._name

    def getType(self):
        return self._type
    
    def getValue(self):
        return self._variableDict[self._name]

    def getVariableList(self):
        return self._variableList

    def getVariableDict(self):
        return self._variableDict

    def frame(self):
        return self._name[:2] 
        
class Argument:
    def __init__(self, typ, value) -> None:
        self._typ: str = typ
        self._value: str = value

    def getValue(self):
        return self._value

class Instruction:
    _instructionList = []

    def __init__(self, order, opcode) -> None:       
        self._order: int = order
        self._opcode: str = opcode.upper()                                
        self._instructionList.append(self)           
        self._arguments = []

    def addArgument(self, argType, argValue):
        self._arguments.append(Argument(argType, argValue))

    def getOrder(self):
        return self._order

    def getOpcode(self):
        return self._opcode

    def getInstructionList(self):
        return self._instructionList

    def getArguments(self):
        return self._arguments

    def numOfArgs(self):
        return len(self._arguments)

### helping functions

def isVariable(symb):
    if re.match(r"(GF|LF|TF)@[a-zA-Z_\-$&%*!?][a-zA-Z_\-$&%*!?0-9]*", symb) == None:
        return False
    else:
        return True

def symbToValueAndType(symb):           ###TODO dat to do vsech fci kde je potreba ale jeste v nich neni
    value = symb.split("@")[1]
    typ = symb.split("@")[0]
    
    if typ == "int":
        return int(value)
    elif typ == "string":
        return value
    elif typ == "bool":
        if value == "true":
            return True
        else:
            return False
    elif typ == "nil":
        return None

### functions for executing of instructions

def execMove(i: Instruction):
    arg1 = i.getArguments()[0]
    arg2 = i.getArguments()[1]
    
    variables = Variable.getVariableDict()
    
    if arg1 not in variables.keys():
        sys.stderr.write("There is no variable called %s\n", arg1)
        exit(54)

    if isVariable(arg2):
        if arg2 not in variables.keys():
            sys.stderr.write("There is no variable called %s\n", arg2)
            exit(54)
        variables[arg1] = variables[arg2]
    else:
        variables[arg1] = arg2

def execCreateFrame():
    pass

def execPushFrame():
    pass

def execPopFrame():
    pass

def execDefvar(i: Instruction):
    var = i.getArguments()[0]

    variables = Variable.getVariableDict()

    if var in variables.keys():
        sys.stderr.write("Variable %s is already defined\n", var)
        exit(52)

    Variable(var, "")

def execCall():
    pass

def execReturn():
    pass

def execPushs(i: Instruction):
    symb = i.getArguments()[0]
    if isVariable(symb):
        symb = Variable.getVariableDict()[symb]
    dataStack.append(symb)

def execPops(i: Instruction):
    var = i.getArguments()[0]
    variables = Variable.getVariableDict()

    if var not in variables.keys():
        sys.stderr.write("There is no variable called %s\n", var)
        exit(54)
    
    try:
        variables[var] = dataStack.pop()
    except IndexError:
        sys.stderr.write("Data stack is empty\n")
        exit(56)

def execArithmetic(i: Instruction):
    operation = i.getOpcode()
    var = i.getArguments()[0]
    symb1 = i.getArguments()[1]
    symb2 = i.getArguments()[2]
    variables = Variable.getVariableDict()

    if var not in variables.keys():
        sys.stderr.write("There is no variable called %s\n", var)
        exit(54)

    if isVariable(symb1):
        symb1 = variables[symb1]
    if isVariable(symb2):
        symb2 = variables[symb2]
    
    try:
        int(symb1)
        int(symb2)
    except:
        sys.stderr.write("At least one of the arguments is not INT\n")
        exit(53)

    if operation == "ADD":
        variables[var] = symb1 + symb2
    elif operation == "SUB":
        variables[var] = symb1 - symb2
    elif operation == "MUL":
        variables[var] = symb1 * symb2
    elif operation == "IDIV":
        variables[var] = symb1//symb2

def execRelational(i: Instruction):
    operation = i.getOpcode()
    var = i.getArguments()[0]
    symb1 = i.getArguments()[1]
    symb2 = i.getArguments()[2]
    variables = Variable.getVariableDict()

    if var not in variables.keys():
        sys.stderr.write("There is no variable called %s\n", var)
        exit(54)

    if isVariable(symb1):
        symb1 = variables[symb1]
    else:
        symb1 = symbToValueAndType(symb1)
    if isVariable(symb2):
        symb2 = variables[symb2]
    else:
        symb2 = symbToValueAndType(symb2)

    ###TODO arguments must be the same type

    if operation == "LT":
        variables[var] = symb1 < symb2
    elif operation == "GT":
        variables[var] = symb1 > symb2
    elif operation == "EG":
        variables[var] = symb1 == symb2

def execLogic(i: Instruction):
    operation = i.getOpcode()
    var = i.getArguments()[0]
    symb1 = i.getArguments()[1]
    if len(i.getArguments()) > 2:
        symb2 = i.getArguments()[2]
        if isVariable(symb2):
            symb2 = variables[symb2]
        else:
            symb2 = symbToValueAndType(symb2)
    variables = Variable.getVariableDict()

    if var not in variables.keys():
        sys.stderr.write("There is no variable called %s\n", var)
        exit(54)

    if isVariable(symb1):
        symb1 = variables[symb1]
    else:
        symb1 = symbToValueAndType(symb1)

    if operation == "AND":
        variables[var] = symb1 and symb2
    elif operation == "OR":
        variables[var] = symb1 or symb2
    elif operation == "NOT":
        variables[var] = not symb1

def execInt2Char(i: Instruction):
    var = i.getArguments()[0]
    symb = i.getArguments()[1]
    variables = Variable.getVariableDict()

    if var not in variables.keys():
        sys.stderr.write("There is no variable called %s\n", var)
        exit(54)

    if isVariable(symb):
        symb = variables[symb]
    else:
        symb = symbToValueAndType(symb)

    try:
        symb = int(symb)
    except:
        sys.stderr.write("Parameter of function INT2CHAR must be integer\n")
        exit(58)

    variables[var] = chr(symb)

def execStri2Int(i: Instruction):
    var = i.getArguments()[0]
    stri = i.getArguments()[1]
    index = i.getArguments()[2]
    variables = Variable.getVariableDict()

    if var not in variables.keys():
        sys.stderr.write("There is no variable called %s\n", var)
        exit(54)

    if isVariable(stri):
        stri = variables[stri]
    else:
        stri = symbToValueAndType(stri)
    if isVariable(stri):
        stri = variables[stri]
    else:
        stri = symbToValueAndType(stri)

    try:
        index = int(index)
    except:
        sys.stderr.write("Index must be integer\n")
        exit(53)

    if index > len(stri)-1:
        sys.stderr.write("Index is out of range\n")
        exit(58)

    variables[var] = ord(stri[index])

def execRead(i: Instruction):
    ###TODO mrknout na cteni ze suboru
    var = i.getArguments()[0]
    typ = i.getArguments()[1]
    variables = Variable.getVariableDict()

    if var not in variables.keys():
        sys.stderr.write("There is no variable called %s\n", var)
        exit(54)

    if typ == "int":
        try:
            Variable(var, int(input()))
        except:
            Variable(var, "nil@nil")
    elif typ == "string":
        Variable(var, input())
    elif typ == "bool":
        if input().lower() == "true":
            Variable(var, "bool@true")
        else:
            Variable(var, "bool@false")

def execWrite(i: Instruction):
    symb = i.getArguments()[0]

    if isVariable(symb):
        symb = Variable.getVariableDict()[symb]
    else:
        symb = symbToValueAndType(symb)

    if type(symb) == bool:
        if symb:
            print("true", end = "")
        else:
            print("false", end = "")
    elif symb == None:
        print("", end = "")
    else:
        print(symb, end = "")

def execConcat(i: Instruction):
    var = i.getArguments()[0]
    symb1 = i.getArguments()[1]
    symb2 = i.getArguments()[2]
    variables = Variable.getVariableDict()

    if var not in variables.keys():
        sys.stderr.write("There is no variable called %s\n", var)
        exit(54)

    if isVariable(symb1):
        symb1 = variables[symb1]
    else:
        symb1 = symbToValueAndType(symb1)
    if isVariable(symb2):
        symb2 = variables[symb2]
    else:
        symb2 = symbToValueAndType(symb2)

    if type(symb1) != str or type(symb2) != str:
        sys.stderr.write("Incorrect type of parameter of function CONCAT\n")
        exit(53)
    
    variables[var] = symb1+symb2

def execStrlen(i: Instruction):
    var = i.getArguments()[0]
    symb = i.getArguments()[1]
    variables = Variable.getVariableDict()

    if var not in variables.keys():
        sys.stderr.write("There is no variable called %s\n", var)
        exit(54)

    if isVariable(symb):
        symb = variables[symb]
    else:
        symb = symbToValueAndType(symb)
    
    if type(symb) != str:
        sys.stderr.write("Incorrect type of parameter of function STRLEN\n")
        exit(53)

    variables[var] = len(symb)

def execGetchar(i: Instruction):
    var = i.getArguments()[0]
    symb1 = i.getArguments()[1]
    symb2 = i.getArguments()[2]
    variables = Variable.getVariableDict()

    if var not in variables.keys():
        sys.stderr.write("There is no variable called %s\n", var)
        exit(54)

    if isVariable(symb1):
        symb1 = variables[symb1]
    else:
        symb1 = symbToValueAndType(symb1)
    if isVariable(symb2):
        symb2 = variables[symb2]
    else:
        symb2 = symbToValueAndType(symb2)

    if type(symb2) != int or type(symb1) != str:
        sys.stderr.write("Incorrect type of parameter of function SETCHAR\n")
        exit(53)

    if symb2 > len(symb1)-1:
        sys.stderr.write("Index is out of range\n")
        exit(58)

    variables[var] = symb1[symb2]     

def execSetchar(i: Instruction):
    var = i.getArguments()[0]
    symb1 = i.getArguments()[1]
    symb2 = i.getArguments()[2]
    variables = Variable.getVariableDict()

    if var not in variables.keys():
        sys.stderr.write("There is no variable called %s\n", var)
        exit(54)

    if isVariable(symb1):
        symb1 = variables[symb1]
    else:
        symb1 = symbToValueAndType(symb1)
    if isVariable(symb2):
        symb2 = variables[symb2]
    else:
        symb2 = symbToValueAndType(symb2)

    value = variables[var]

    if symb2 > len(value)-1 or symb2 == "":
        sys.stderr.write("Index is out of range\n")
        exit(58)

    if type(value) != str or type(symb1) != int or type(symb2) != str:
        sys.stderr.write("Incorrect type of parameter of function GETCHAR\n")
        exit(53)
    
    variables[var] = value[:symb1] + symb2[0] + value[symb1+1:]

def execType(i: Instruction):
    var = i.getArguments()[0]
    symb = i.getArguments()[1]
    variables = Variable.getVariableDict()

    if var not in variables.keys():
        sys.stderr.write("There is no variable called %s\n", var)
        exit(54)

    if isVariable(symb):
        symb = variables[symb]
    else:
        symb = symbToValueAndType(symb)

    if type(symb) == list:
        variables[var] = ""
    else:
        variables[var] = str(type(symb))

def execLabel(i: Instruction):
    label = i.getArguments()[0]
    
    if label in labelList:
        sys.stderr.write("Label %s is already existing\n", label)
        exit(52)
    
    labelList.append(label)

def execJump(i: Instruction):
    label = i.getArguments()[0]
    
    if label not in labelList:
        sys.stderr.write("There is no label called %s\n", label)
        exit(52)

    ###TODO jumpy

def execExit(i:Instruction):
    symb = i.getArguments()[1]
    variables = Variable.getVariableDict()

    if isVariable(symb):
        symb = variables[symb]
    else:
        symb = symbToValueAndType(symb)
    
    if type(symb) != int or symb < 0 or symb > 49:
        sys.stderr.write("Exit code must be integer from 0 to 49 (both included)\n")
        exit(57)

def execDprint(i: Instruction):
    symb = i.getArguments()[1]
    variables = Variable.getVariableDict()

    if isVariable(symb):
        symb = variables[symb]
    else:
        symb = symbToValueAndType(symb)
    
    sys.stderr.write(symb)

###TODO break

### main body of the program

if __name__ == '__main__':
    
    # argparse
    ap = argparse.ArgumentParser()
    ap.add_argument("--source", nargs=1, help="file with XML representation of source code")
    ap.add_argument("--input", nargs=1, help="file with inputs for interpretation of given source code")

    args = vars(ap.parse_args())

    print(args.values())

    if (args["source"] == None and args["input"] == None) or len(args) > 2:
        sys.stderr.write("Wrong number of arguments\n")
        exit(10)

    sourceFile = str(args["source"])[2:-2]


    # xml load
    try:
        tree = xmlEtree.parse(sourceFile)
    except:
        sys.stderr.write("Incorrect XML format\n")
        exit(31)

    root = tree.getroot()
    

    # xml check
    if root.tag != "program":
        sys.stderr.write("Incorrect XML format\n")
        exit(32)

    for child in root:
        if child.tag != "instruction":
            sys.stderr.write("Incorrect XML format\n")
            exit(32)
        childAttrib = list(child.attrib.keys())
        if not("order" in childAttrib) or not("opcode" in childAttrib):
            sys.stderr.write("Incorrect XML format\n")
            exit(32)

        for subelement in child:
            if not(re.match(r"arg[123]", subelement.tag)):
                sys.stderr.write("Incorrect XML format\n")
                exit(32)


    # xml to instruction
    for element in root:
        instruction = Instruction(element.attrib["order"], element.attrib["opcode"])
        for subelement in element:
            instruction.addArgument(subelement.attrib["type"], subelement.text)
    
    Instruction.getInstructionList(instruction).sort(key=lambda x: int(x._order)) 

    for instruction in Instruction.getInstructionList(instruction):
        
        if instruction.getOpcode() == "MOVE":
            execMove(instruction)
        elif instruction.getOpcode() == "CREATEFRAME":
            execCreateFrame()
        elif instruction.getOpcode() == "POPFRAME":
            execPushFrame()
        elif instruction.getOpcode() == "PUSHFRAME":
            execPopFrame()
        elif instruction.getOpcode() == "DEFVAR":
            execDefvar(instruction)
        elif instruction.getOpcode() == "PUSHS":
            execPushs(instruction)
        elif instruction.getOpcode() == "POPS":
            execPops(instruction)
        elif instruction.getOpcode() == "ADD" or instruction.getOpcode() == "SUB" or instruction.getOpcode() == "MUL" or instruction.getOpcode() == "IDIV":
            execArithmetic(instruction)
        elif instruction.getOpcode() == "LT" or instruction.getOpcode() == "GT" or instruction.getOpcode() == "EQ":
            execRelational(instruction)
        elif instruction.getOpcode() == "AND" or instruction.getOpcode() == "OR" or instruction.getOpcode() == "NOT": 
            execLogic(instruction)
        elif instruction.getOpcode() == "INT2CHAR":
            execInt2Char(instruction)
        elif instruction.getOpcode() == "STRI2INT":
            execStri2Int(instruction)
        elif instruction.getOpcode() == "READ":
            execRead(instruction)
        elif instruction.getOpcode() == "STRLEN":
            execStrlen(instruction)
        elif instruction.getOpcode() == "CONCAT":
            execConcat(instruction)
        elif instruction.getOpcode() == "GETCHAR":
            execGetchar(instruction)
        elif instruction.getOpcode() == "SETCHAR":
            execSetchar(instruction)
        elif instruction.getOpcode() == "TYPE":
            execType(instruction)
        elif instruction.getOpcode() == "LABEL":
            execLabel(instruction)
        elif instruction.getOpcode() == "JUMP":
            execJump(instruction)
        elif instruction.getOpcode() == "JUMPIFEQ" or instruction.getOpcode() == "JUMPIFNEQ":
            #execJumpif(instruction)
            pass
        elif instruction.getOpcode() == "EXIT":
            execExit(instruction)
        elif instruction.getOpcode() == "DPRINT":
            execDprint(instruction)
        elif instruction.getOpcode() == "BREAK":
            #execBreak()
            pass
        else:
            sys.stderr.write("Unknown instruction\n")
            exit(53)
            
        