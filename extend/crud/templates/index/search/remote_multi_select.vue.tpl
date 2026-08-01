                    <el-form-item label="<%label%>">
                        <ga-select-remote
                            v-model="advanced.<%prop%>"
                            :remote-url="'<%url%>'"
                            :props="{ value: '<%valueField%>', label: '<%labelField%>' }"
                            multiple
                            collapse-tags
                            clearable
                            placeholder="<%placeholder%>"
                            class="!w-48"
                        />
                    </el-form-item>
