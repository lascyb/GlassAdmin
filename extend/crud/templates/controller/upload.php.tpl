    /**
     * <%action%> 上传<%label%>（Attachment::upload）
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

        try {
            $att = Attachment::upload($upload, AttachmentUploadOptions::<%factory%>('public', '<%api%>', $this->auth?->adminId, '<%dir%>'));
        } catch (InvalidArgumentException $e) {
            return $this->fail(400, $e->getMessage());
        } catch (RuntimeException $e) {
            return $this->fail(500, $e->getMessage());
        }

        return $this->ok(['path' => (string) $att->getAttr('path')]);
    }
