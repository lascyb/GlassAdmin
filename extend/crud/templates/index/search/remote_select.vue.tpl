                    <el-form-item label="<%label%>">
                        <ga-select-remote
                            v-model="advanced.<%prop%>"
                            :remote-url="'<%url%>'"
                            :props="{ value: '<%valueField%>', label: '<%labelField%>' }"
                            clearable
                            placeholder="<%placeholder%>"
                            class="!w-40"
                        />
                    </el-form-item>
