                <el-form-item label="<%label%>"<%required%>>
                    <ga-select-remote
                        v-if="detailReady"
                        :key="`<%key%>-${form.id ?? 'new'}`"
                        v-model="form.<%prop%>"
                        :remote-url="'<%url%>'"
                        :props="{ value: '<%valueField%>', label: '<%labelField%>' }"
                        :init-options="<%initVar%>"<%multiAttrs%>
                        clearable
                        placeholder="<%placeholder%>"
                        class="w-full"
                    />
                </el-form-item>
