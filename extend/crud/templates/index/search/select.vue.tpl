                    <el-form-item label="<%label%>">
                        <el-select v-model="advanced.<%prop%>" clearable placeholder="全部" class="!w-28">
                            <el-option
                                v-for="o in <%optKey%>"
                                :key="o.value"
                                :value="o.value"
                                :label="o.label"
                            />
                        </el-select>
                    </el-form-item>
