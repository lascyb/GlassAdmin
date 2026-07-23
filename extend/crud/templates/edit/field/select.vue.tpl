                <el-form-item label="<%label%>"<%required%>>
                    <el-select v-model="form.<%prop%>" class="w-full" clearable placeholder="<%placeholder%>">
                        <el-option
                            v-for="o in <%optKey%>"
                            :key="o.value"
                            :value="o.value"
                            :label="o.label"
                        />
                    </el-select>
                </el-form-item>
