                    <el-form-item label="<%label%>">
                        <ga-select-remote
                            v-model="advanced.<%prop%>"
                            :remote-url="'<%url%>'"
                            :props="{ value: 'id', label: 'name' }"<%multiAttrs%>
                            clearable
                            placeholder="<%placeholder%>"
                            class="<%className%>"
                        />
                    </el-form-item>
