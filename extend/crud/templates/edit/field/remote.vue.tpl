                <el-form-item label="<%label%>"<%required%>>
                    <ga-select-remote
                        v-if="detailReady"
                        :key="`<%key%>-${form.id ?? 'new'}`"
                        v-model="form.<%prop%>"
                        :remote-url="'<%url%>'"
                        :props="{ value: 'id', label: 'name' }"
                        :init-options="<%initVar%>"<%multiAttrs%>
                        clearable
                        placeholder="输入关键词搜索<%label%>"
                        class="w-full"
                    />
                </el-form-item>
