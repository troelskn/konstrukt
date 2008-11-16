<?php
set_include_path(
  get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/../includes/');

require_once 'restructured.php';

class Pepper_DocBlockLexer
{
  public $tokens = Array();

  function process($input) {
    $input = preg_replace(Array('~^(/[*]{2})~','~([*]{1}/)~'), "", $input);
//    $input = trim(preg_replace('~^(\s*[*]{1}\s{1})~m', "", $input));
    $input = trim(preg_replace('~^(\s*[*]{1}[ ]{0,1})~m', "", $input));
    $input = preg_replace('/(\n\r|\r\n)/', "\n", $input);

    $result = Array();
    $current = Array('__', "");
    foreach (explode("\n", preg_replace("/\015\012|\015|\012/", "\n", $input)) as $line) {
      if (preg_match('~^@([\w]+)[\s]+(.*)$~', $line, $matches)) {
        $result[] = $current;
        $current = Array($matches[1], $matches[2]);
      } else if (preg_match('~^@([\w]+)$~', $line, $matches)) {
        $result[] = $current;
        $current = Array($matches[1], NULL);
      } else {
        if (!empty($current[1])) {
          $current[1] .= "\n";
        }
        $current[1] .= $line;
      }
    }
    $result[] = $current;
    $this->setUp();
    foreach ($result as $token) {
      $this->processToken(strtolower($token[0]), $token[1]);
    }
    return $this->tokens;
  }

  function setUp() {
    $this->tokens = Array(
      'param' => Array(),
      'var' => 'mixed',
      'return' => 'mixed',
      'deprecated' => FALSE,
    );
  }

  function processToken($symbol, $token) {
    switch ($symbol) {
      case '__' :   $this->processTEXT($token);
            break;
      case 'param' :  $this->processPARAM($token);
            break;
      case 'var' :  $this->processVAR($token);
            break;
      case 'deprecated' :  $this->processDEPRECATED($token);
            break;
      case 'returns' :
      case 'return' : $this->processRETURNS($token);
            break;
    }
  }

  function processDEPRECATED($token) {
    $this->tokens['deprecated'] = TRUE;
  }

  function processTEXT($token) {
    $token = preg_replace("/\015\012|\015|\012/", "\n", $token);
//    $token = str_replace("\n\n", "<br/>", $token);
    $this->tokens['documentation'] = $token;
  }

  function processPARAM($token) {
    $_t = $token;
    $token = "";
    foreach (preg_split("/\015\012|\015|\012/", $_t) as $line) {
      $token .= " ".trim($line);
    }
    $token = trim($token);
    if (preg_match('/^([\w_]+)\s+\$([\w_]+)\s+(.+)$/', $token, $matches)) {
      // @param datatype $paramname description
      $this->tokens['param'][$matches[2]] = Array(
        'type' => $matches[1],
        'name' => $matches[2],
        'description' => $matches[3],
      );
    } else if (preg_match('/^([\w_]+)\s+\$([\w_]+)\s*$/', $token, $matches)) {
      // @param datatype $paramname
      $this->tokens['param'][$matches[2]] = Array(
        'type' => $matches[1],
        'name' => $matches[2],
        'description' => NULL,
      );
    }
  }

  function processVAR($token) {
    $this->tokens['var'] = $token;
  }

  function processRETURNS($token) {
    $this->tokens['return'] = $token;
  }
}

class Pepper_Discoverer
{
  public $classes = Array();

  function getFiles($source) {
    if (is_file($source)) {
      return Array($source);
    } else if (is_dir($source)) {
      $d = dir($source);
      $a = Array();
      while (false !== ($entry = $d->read())) {
        if ($entry{0} != '.') {
          foreach ($this->getFiles(rtrim($d->path, "/")."/".$entry) as $file) {
            $a[] = $file;
          }
        }
      }
      $d->close();
      return $a;
    }
    throw new Exception("File not found");
  }

  function readFile($source) {
    foreach ($this->getFiles($source) as $file) {
      require_once($file);
      foreach ($this->getClassesFromFile($file) as $className) {
        $this->classes[strtolower($className)] = new ReflectionClass($className);
      }
    }
  }

  function getClassesFromFile($file) {
    $state = 0;
    $matches = Array();
    foreach (token_get_all(file_get_contents($file)) as $token) {
      if (is_array($token)) {
        list ($token, $text) = $token;
      } else if (is_string($token)) {
        $text = $token;
        $token = NULL;
      }
      if ($state == 0) {
        if ($token == T_CLASS || $token == T_INTERFACE) {
          $state = 1;
        }
      } else if ($token == T_STRING) {
        $matches[] = $text;
        $state = 0;
      }
    }
    return $matches;
  }

  function getClasses() {
    return array_values($this->classes);
  }

}

class Pepper_StreamWriter
{
  protected $handle;

  function __construct($handle) {
    $this->handle = $handle;
  }

  function write($data) {
    fwrite($this->handle, $data);
  }

  function close() {
    fclose($this->handle);
  }
}

class Pepper_GraphVizWriter
{
  protected $handle;
  protected $binary;
  protected $buffer = "";

  function __construct($handle, $binary = 'dot') {
    $this->handle = $handle;
    $this->binary = $binary;
  }

  function write($data) {
    $this->buffer .= $data;
  }

  function close() {
    fwrite($this->handle, $this->render($this->buffer));
    fclose($this->handle);
  }

  function render($markup, $format = 'png') {
    $descriptorspec = Array(
      0 => Array("pipe", "r"),  // stdin
      1 => Array("pipe", "w"),  // stdout
      2 => Array("pipe", "w")   // stderr
    );
    $pipes = NULL;
    $cwd = getcwd();
    $env = Array();

    $process = proc_open($this->binary.' -T'.$format, $descriptorspec, $pipes, $cwd, $env);

    if (is_resource($process)) {
       // $pipes now looks like this:
       // 0 => writeable handle connected to child stdin
       // 1 => readable handle connected to child stdout
       // Any error output will be appended to /tmp/error-output.txt

       fwrite($pipes[0], $markup);
       fclose($pipes[0]);

       $result = stream_get_contents($pipes[1]);
       fclose($pipes[1]);
       $return_value = proc_close($process);
       return $result;
    }
    throw new Exception("Can't open process");
  }
}

class Pepper_Transformer
{
  protected $template = "html";
  protected $outstream;

  protected $important = Array();

  function __construct($outstream, $template = "html") {
    $this->template = $template;
    $this->outstream = $outstream;
    ob_start();
    include("templates/".$this->template."/top.tmpl.php");
    $this->outstream->write(ob_get_clean());
  }

  function finalize() {
    $important = $this->important;
    ob_start();
    include("templates/".$this->template."/bottom.tmpl.php");
    $this->outstream->write(ob_get_clean());
    $this->outstream->close();
  }

  function processClass(ReflectionClass $class) {
    $this->outstream->write($this->transformClass($class));
  }

  function transformDocBlock($str) {
    $lexer = new Pepper_DocBlockLexer();
    $result = $lexer->process($str);
    if (isset($result['documentation'])) {
      $parser = new restructured_Parser();
      $transformer = new restructured_Transformer();
      $transformer->newlines = FALSE;
      $result['documentation'] = $transformer->transform($parser->parse($result['documentation']));
    }
    return $result;
  }

  function transformClass(ReflectionClass $class) {
    $is_internal = $class->isInternal() ? 'internal' : 'user-defined';
    $is_abstract = $class->isAbstract() ? ' abstract' : '';
    $is_final = $class->isFinal() ? ' final' : '';
    $is_interface = $class->isInterface() ? 'interface' : '';
    $type = $class->isInterface() ? 'interface' : 'class';
    $name = $class->getName();
    $extends = @$class->getParentClass()->name;
    if ($class->isInternal() || $class->isInterface()) {
      $this->important[] = $name;
    }
    $implements = Array();
    foreach ($class->getInterfaces() as $i) {
      $implements[] = $i->name;
    }
    $implements_inherited = Array();
    if ($class->getParentClass()) {
      foreach ($class->getParentClass()->getInterfaces() as $i) {
        $implements_inherited[] = $i->name;
      }
    }
    $implements_directly = array_diff($implements, $implements_inherited);

    $modifiers = Reflection::getModifierNames($class->getModifiers());
    extract($this->transformDocBlock($class->getDocComment()));

    $this->__global_hack = Array();
    $properties = Array();
    $properties_inherited = Array();
    foreach ($class->getProperties() as $property) {
      if ($property->getDeclaringClass()->name == $class->name) {
        $properties[] = $this->transformProperty($property, FALSE);
      } else {
        $properties_inherited[] = $this->transformProperty($property, TRUE);
      }
    }
    $methods = Array();
    $methods_inherited = Array();
    foreach ($class->getMethods() as $method) {
      if ($method->getDeclaringClass()->name == $class->name) {
        $methods[] = $this->transformMethod($method);
      } else {
        $methods_inherited[] = $this->transformMethod($method);
      }
    }
    ob_start();
    include("templates/".$this->template."/class.tmpl.php");
    return ob_get_clean();
  }

  function transformProperty(ReflectionProperty $property, $is_inherited) {
    $is_public = $property->isPublic() ? ' public' : '';
    $is_private = $property->isPrivate() ? ' private' : '';
    $is_protected = $property->isProtected() ? ' protected' : '';
    $is_static = $property->isStatic() ? ' static' : '';
    $name = $property->getName();
    $class = $property->getDeclaringClass()->name;
    $modifiers = Reflection::getModifierNames($property->getModifiers());
    extract($this->transformDocBlock($property->getDocComment()));

    ob_start();
    include("templates/".$this->template."/property.tmpl.php");
    return ob_get_clean();
  }

  function transformParameter(ReflectionParameter $param, $meta) {
    $name = $param->getName();
    if ($param->getClass() instanceOf ReflectionClass) {
      $type = $param->getClass()->name;
    } else {
      $type = @$meta['type'];
    }
    if (!$type) {
      $type = 'mixed';
    }
    $description = @$meta['description'];
    $allow_null = $param->allowsNull();
    $passed_by_ref = $param->isPassedByReference();
    $is_optional = $param->isOptional();
    ob_start();
    include("templates/".$this->template."/parameter.tmpl.php");
    return ob_get_clean();
  }

  function transformMethod(ReflectionMethod $method) {
    $is_internal = $method->isInternal() ? 'internal' : 'user-defined';
    $is_abstract = $method->isAbstract() ? ' abstract' : '';
    $is_final = $method->isFinal() ? ' final' : '';
    $is_public = $method->isPublic() ? ' public' : '';
    $is_private = $method->isPrivate() ? ' private' : '';
    $is_protected = $method->isProtected() ? ' protected' : '';
    $is_static = $method->isStatic() ? ' static' : '';
    $name = $method->getName();
    $class = $method->getDeclaringClass()->name;
    $is_constructor = $method->isConstructor() ? 'the constructor' : 'a regular method';
    $modifiers = Reflection::getModifierNames($method->getModifiers());
    extract($this->transformDocBlock($method->getDocComment()));

    $parameters = Array();
    $parameters_concat = "";
    $_optional_count = 0;
    foreach ($method->getParameters() as $_param) {
      $parameters[$_param->getName()] = $this->transformParameter($_param, @$param[$_param->getName()]);
      if ($_param->isOptional()) {
        $parameters_concat .= " [ ";
        $_optional_count++;
      }
      if ($parameters_concat != "" && $parameters_concat != " [ ") {
        $parameters_concat .= ", ";
      }
      $parameters_concat .= $parameters[$_param->getName()];
    }
    $parameters_concat .= str_repeat(" ] ", $_optional_count);

    ob_start();
    include("templates/".$this->template."/method.tmpl.php");
    return ob_get_clean();
  }
}
