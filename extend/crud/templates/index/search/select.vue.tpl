                    <el-form-item label="<%label%>">
                        <el-select v-model="advanced.<%prop%>" clearable placeholder="全部" class="!w-28">
                            <el-option
                                v-for="(item, value) in <%mapKey%>"
                                :key="value"
                                :value="Number(value)"
                                :label="item.label"
                            />
                        </el-select>
                    </el-form-item>
