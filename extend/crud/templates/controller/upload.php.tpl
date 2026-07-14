    /**
     * <%action%> 上传<%label%>
     */
    public function <%action%>(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }
        $upload = $request->file('file');
        if (!$upload instanceof UploadedFile) {
            return $this->fail(400, '请上传文件');
        }
        if (!$upload->isValid()) {
            return $this->fail(400, '文件无效或过大');
        }
        $size = (int) $upload->getSize();
        if ($size <= 0 || $size > self::UPLOAD_MAX_BYTES) {
            return $this->fail(400, '文件大小需在 1B～2MB');
        }
        $rawExt = strtolower($upload->extension());
        if ($rawExt === 'jpeg') {
            $rawExt = 'jpg';
        }
        $allowed = ['jpg', 'png', 'gif', 'webp'];
        if (!in_array($rawExt, $allowed, true)) {
            return $this->fail(400, '仅支持 jpg、png、gif、webp');
        }

        $dir = rtrim(root_path('public/storage/<%dir%>'), DIRECTORY_SEPARATOR);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return $this->fail(500, '存储目录不可用');
        }
        $baseName = bin2hex(random_bytes(16)) . '.' . $rawExt;
        try {
            $saved = $upload->move($dir, $baseName);
        } catch (FileException) {
            return $this->fail(500, '保存失败');
        }

        return $this->ok(['path' => '<%dir%>/' . $saved->getFilename()]);
    }
