                <el-form-item label="<%label%>">
                    <div class="flex items-center gap-3">
                        <el-upload :show-file-list="false" accept="image/*" :http-request="<%uploadFn%>">
                            <el-image
                                v-if="form.<%prop%>"
                                :src="resolveStorageUrl(form.<%prop%>)"
                                fit="cover"
                                class="h-20 w-20 rounded border border-[var(--el-border-color)]"
                            />
                            <el-button v-else :icon="Plus">上传<%label%></el-button>
                        </el-upload>
                        <el-button v-if="form.<%prop%>" link type="danger" @click="form.<%prop%> = ''">移除</el-button>
                    </div>
                </el-form-item>
