                    <el-form-item label="<%label%>">
                        <el-select v-model="advanced.<%prop%>" clearable placeholder="全部" class="!w-28">
                            <el-option :value="1" label="是" />
                            <el-option :value="0" label="否" />
                        </el-select>
                    </el-form-item>
