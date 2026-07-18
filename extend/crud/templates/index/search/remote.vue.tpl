                    <el-form-item label="<%label%>">
                        <ga-select-remote
                            v-model="advanced.<%prop%>"
                            :remote-url="'<%url%>'"
                            :props="{ value: '<%valueField%>', label: '<%labelField%>' }"<%multiAttrs%>
                            clearable
                            placeholder="<%placeholder%>"
                            class="<%className%>"
                        />
                    </el-form-item>
