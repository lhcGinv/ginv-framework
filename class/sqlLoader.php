<?php
class sqlLoader
{
    private $template_dir;
    private $temp_dir;


    public function __construct()
    {
        $this->template_dir = base_path('template');
        $this->temp_dir = base_path('temp');
        $files = $this->getFiles($this->template_dir);
        if (count($files) > 0) {
            $this->load($files);
        }
    }

    /**
     * 读取模板文件生成sql函数
     * @param $files
     */
    public function load($files)
    {
        foreach ($files as $f) {
            $md5      = md5_file($f);
            $pos      = strrpos($f, '/');
            $basename = substr($f, $pos);
            $basename = str_replace('.blade.php', '', $basename);
            $filename = $basename . $md5 . '.php';
            $file     = $this->temp_dir . $filename;
            // 新模板或者改动过模板
            if (!file_exists($file)) {
                $this->unlinkExpiredFiles($basename);
                $subject = file_get_contents($f);
                $subject = $this->replaceComment($subject);
                $subject = '<?php
' . $subject;
                $subject = $this->handelSection($subject);
                file_put_contents($file, $subject);
            }
            require_once $file;
        }
    }

    private function handelSection($subject)
    {
        $pattern = "/@section[\s\S]*endsection/U";
        return preg_replace_callback($pattern, function ($match) {
            $content = '';
            foreach ($match as $section) {
                $section = $this->replaceSection($section);
                $section = preg_replace('/@/', '";@', $section, 1);
                $section = $this->handelSpaceContent($section);
                $section = $this->replaceIf($section);
                $section = $this->replaceEndIf($section);
                $section = $this->replaceEndSection($section);
                $section = $this->replaceIn($section);
                $content .= $section;
            }
            return $content;
        }, $subject);
    }

    private function replaceComment($subject) {
        $pattern = "/{{--[\s\S]*--}}/U";
        return preg_replace($pattern, '', $subject);
    }

    private function replaceSection($subject)
    {
        $pattern = "/@section\s*\(.*\)/U";
        return preg_replace_callback($pattern, function ($match) {
            $str = $match[0];
            $name = $this->camelCaseName($str);
            return 'function ' . $name . ' ($params=[]) {
    extract($params);
    $sql ="';
        }, $subject);
    }

    private function replaceEndSection($subject)
    {
        $pattern = "/@endsection/U";
        return preg_replace_callback($pattern, function () {
            return '    return $sql;
}';
        }, $subject);
    }

    private function handelSpaceContent($subject) {
        $pattern = "/@endif[\s\S]*@/U";
        return preg_replace_callback($pattern, function ($match) {
            $str = $match[0];
            $str = substr($str,6,strlen($str) - 7);
            $str = trim($str);
            if ($str != "") {
                return '@endif
                
    $sql .="' . $str . '";
            @';
            } else {
                return $match[0];
            }
        }, $subject);
    }

    /**
     * 替换{{if ***}}里面的内容
     * @param $subject
     *
     * @return null|string|string[]
     */
    private function replaceIf($subject)
    {
        $pattern = "/@if\s*\(.*\)/U";
        return preg_replace_callback($pattern, function ($match) {
            $str    = $match[0];
            $str = substr($str, 1);
            return '
    '. $str . ' {
        $sql.="';
        }, $subject);
    }

    private function replaceEndIf($subject) {
        $pattern = "/@endif/U";
        return str_replace('@endif', '";
    }', $subject);
    }

    private function replaceIn($subject) {
        $pattern = "/in\s*\(:.*\)/U";
        return preg_replace_callback($pattern, function ($match) {
            $str    = $match[0];
            $in_val = $this->getInVal($str);
            return '";
        $sql .= " in (". assembleSqlIn("'.$in_val.'", count($' . $in_val . ')). ")';
        }, $subject);
    }

    /**
     * 驼峰式格式化临时文件的方法名
     * @param $subject
     *
     * @return string
     */
    private function getInVal($subject)
    {
        preg_match('/:.*\)$/i', $subject, $m);
        if (!empty($m)) {
            return substr($m[0],1, strlen($m[0])-2);
        }
    }

    /**
     * @param $subject
     *
     * @return null|string|string[]
     */
    private function replaceContent($subject)
    {
        $pattern = "/}[\s\S]*\";/U";
        return preg_replace_callback($pattern, function ($match) {
            foreach ($match as $v) {
                // echo $v."<br>";
                if (preg_match("/}[\s]*\";/U", $v)) {
                    return '}';
                }
                if (strstr('tt' . $v, '$sql.=') != null) {
                    return $v;
                }
                $v = trim($v, "}");
                $v = trim($v);
                return '}
    $sql.="
        ' . $v;
            }
            return '';
        }, $subject);
    }

    /**
     * 驼峰式格式化临时文件的方法名
     * @param $subject
     *
     * @return string
     */
    protected function camelCaseName($subject)
    {
        preg_match('/"(.*)"/i', $subject, $m);
        $name = $m[0];
        $name = str_replace('.', ' ', $name);
        $name = trim($name, '"');
        $name = 'ginV ' . $name;
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        $name = str_replace('.', '$', $name);
        return lcfirst($name);
    }

    /**
     * 获取当前所有的sql目标文件
     * @param        $dir
     * @param string $suffix
     *
     * @return array
     */
    public function getFiles($dir, $suffix = '.blade.php')
    {
        $files = [];
        if (@$handle = opendir($dir)) {
            while (($file = readdir($handle)) !== false) {
                if ($file != ".." && $file != ".") { //排除根目录
                    if (is_dir($dir . "/" . $file)) { //如果是子文件夹，就进行递归
                        $files[$file] = $this->getFiles($dir . "/" . $file);
                    } else {
                        //不然就将文件的名字存入数组
                        $pos = strrpos($file, $suffix);
                        $len = strlen($suffix);
                        if ($pos && $pos == strlen($file) - $len) {
                            $files[] = $dir . "/" . $file;
                        }
                    }
                }
            }
            closedir($handle);
        }
        return $files;
    }

    /**
     * 删除失效的临时sql文件
     * @param $base
     */
    public function unlinkExpiredFiles($base)
    {
        // dd($base);
        $files = $this->getFiles($this->temp_dir, '.php');
        foreach ($files as $f) {
            $pattern = '/\\' . $base . '.{32}\.php$/';
            $count   = preg_match($pattern, $f);
            if ($count > 0) {
                unlink($f);
            }
        }
    }

}